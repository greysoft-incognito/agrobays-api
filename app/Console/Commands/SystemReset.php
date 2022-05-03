<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Request;
use Madnest\Madzipper\Madzipper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\SlackAlerts\Facades\SlackAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use App\Models\Food;
use App\Models\FruitBayCategory;
use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;

class SystemReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:reset
                            {action? : Action to perform [reset, backup, restore]}
                            {--w|wizard : Let the wizard help you manage system reset and restore.}
                            {--r|restore : Restore the system to the last backup or provide the --signature option to restore a known backup signature.}
                            {--s|signature= : Set the backup signature value to restore a particular known backup. E.g. 2022-04-26_16-05-34.}
                            {--b|backup : Do a complete system backup before the reset.}
                            {--d|delete : If the restore option is set, this option will delete the backup files after successfull restore.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the system (Clears database and removes related media files)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');
        $backup = $this->option('backup');
        $restore = $this->option('restore');
        $signature = $this->option('signature');
        $delete = $this->option('delete');
        $wizard = $this->option('wizard');

        if ($action === 'backup') {
            return $this->backup();
        } elseif ($action === 'restore') {
            $signatures = collect(Storage::allFiles('backup'))
                ->filter(fn($f)=>Str::contains($f, '.sql'))
                ->map(fn($f)=>Str::of($f)->substr(0, -4)->replace(['backup','/-'], ''))->sortDesc()->values()->all();
            $signature = $this->choice('Backup Signature (Latest shown first):', $signatures, 0, 3);
            $delete = $this->choice('Delete Signature after restoration?', ['No', 'Yes'], 1, 2);
            return $this->restore($signature, $delete==='Yes');
        }

        if ($wizard)
        {
            if (!app()->runningInConsole()) {
                $this->error("This action can only be run in a CLI.");
                return 0;
            }
            $action = $this->choice('What do you want to do?', ['backup', 'restore', 'reset'], 2, 3);

            if ($action === 'backup') {
                return $this->backup();
            }
            elseif ($action === 'restore')
            {
                $signatures = collect(Storage::allFiles('backup'))
                    ->filter(fn($f)=>Str::contains($f, '.sql'))
                    ->map(fn($f)=>Str::of($f)->substr(0, -4)->replace(['backup','/-'], ''))->sortDesc()->values()->all();
                $signature = $this->choice('Backup Signature (Latest shown first):', $signatures, 0, 3);
                $delete = $this->choice('Delete Signature after restoration?', ['No', 'Yes'], 1, 2);
                return $this->restore($signature, $delete==='Yes');
            }
            elseif ($action === 'reset')
            {
                $backup = $this->choice('Would you want do a sytem backup before reset?', ['No', 'Yes'], 1, 2);
                // Reset the system
                return $this->reset($backup === 'Yes');
            }
            return 0;
        } else {
            // Restore the system backup
            if ($restore) {
                return $this->restore($signature, $delete);
            }

            // Reset the system
            return $this->reset($backup);
        }
    }

    /**
     * Perform a backup of the system's database and uploaded media content
     *
     * @return integer
     */
    protected function backup(): int
    {
        $this->info(Str::of(env('APP_URL'))->trim('/http://https://') . " Is being backed up.");
        SlackAlert::message(Str::of(env('APP_URL'))->trim('/http://https://') . " Is being backed up.");

        SlackAlert::message("System backup started at: ". Carbon::now());
        $this->info("System backup started.");

        $backupPath = storage_path("app/backup/");

        // Backup the database
        $filename = "backup-" . \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
        $command = "mysqldump --skip-comments"
        ." --user=" . env('DB_USERNAME')
        ." --password=" . env('DB_PASSWORD')
        . " --host=" . env('DB_HOST')
        . " " . env('DB_DATABASE') . " > " . $backupPath . $filename . ".sql";
        $returnVar = NULL; $output = NULL;
        exec($command, $output, $returnVar);

        // Backup the files.
        $zip = new Madzipper;
        $zip->make($backupPath . $filename . '.zip')->folder('public')->add([storage_path("app/public")]);
        $signature = Str::of($filename)->substr(0, -4)->replace(['backup-','/'], '');

        // Generate Link
        if (app()->runningInConsole()) {
            $link = app()->runningInConsole() ? $this->choice('Should we generate a link to download your backup files?', ['No', 'Yes'], 1, 2) : 'Yes';
            if ($link === 'Yes' && file_exists($backupPath . $filename . ".sql")) {

                // Generate a downloadable link for this backup
                $zip = new Madzipper;
                $zip->make(storage_path("app/secure/$filename.zip"))->folder('backup')
                    ->add([$backupPath . $filename . ".sql", $backupPath . $filename . ".zip"]);

                $link_url = route('secure.download', $filename . ".zip");

                $mail = app()->runningInConsole() ? $this->choice('Should we mail you the link?', ['No, I\'ll copy from here.', 'Yes, mail me'], 1, 2) : 'No';

                if ($mail === 'Yes, mail me' && $zip->getFilePath()) {
                    $address = $this->ask('Email Address:');
                    Mail::send('email', [
                        'name'=> ($name = collect(explode('@', $address)))->last(),
                        'message_line1' => 'You requested that we mail you a link to download your system backup.',
                        'cta' => ['link' => $link_url, 'title' => 'Download']
                    ], function($message) use($address, $name, $filename) {
                       $message->to($address, $name)->subject('Backup');
                    //    $message->attach(storage_path("app/secure/$filename.zip"));
                       $message->from(env('MAIL_FROM_ADDRESS'), config('settings.site_name'));
                    });

                    SlackAlert::message("We have sent the download link to your backup file to $address.");
                    $this->info("We have sent the download link to your backup file to $address.");
                } elseif (!$zip->getFilePath()) {
                    SlackAlert::message("You have requested that we we mail you the link to your backup file but we failed to fetch the link.");
                    $this->error("Failed to fetch link.");
                } else {
                    SlackAlert::message("Download your backup file through this link: $link_url.");
                    $this->info("Download your backup file through this link: $link_url.");
                }
            } elseif (!file_exists($backupPath . $filename . ".sql")) {
                $this->error("Failed to send link.");
            }
        } else {
            // Generate a downloadable link for this backup
            $zip = new Madzipper;
            $zip->make(storage_path("app/secure/$filename.zip"))->folder('backup')
                ->add([$backupPath . $filename . ".sql", $backupPath . $filename . ".zip"]);

            $link_url = route('secure.download', $filename . ".zip");
            $this->info("Download your backup file through this link: $link_url.");
        }

        SlackAlert::message("System backup completed at: ". Carbon::now());
        $this->info("System backup completed successfully (Signature: $signature).");
        return 0;
    }

    /**
     * Perform a system restoration from the last or one of the available backups
     *
     * @param string|null $signature
     * @param boolean $delete
     * @return integer
     */
    protected function restore($signature, $delete = false): int
    {
        $this->info(Str::of(env('APP_URL'))->trim('/http://https://') . " Is being restored.");
        SlackAlert::message(Str::of(env('APP_URL'))->trim('/http://https://') . " Is being restored.");

        // Delete public Symbolic links
        file_exists(public_path('media')) && unlink(public_path('media'));
        file_exists(public_path('storage')) && unlink(public_path('storage'));
        file_exists(public_path('uploads')) && unlink(public_path('uploads'));

        SlackAlert::message("System restore started at: ". Carbon::now());
        $this->info("System restore started.");

        // Create public Symbolic links
        Artisan::call('storage:link');
        $this->info("Public Symbolic links deleted.");

        $backupPath = storage_path("app/backup/");
        if ($signature) {
            $database = "backup-" . $signature . ".sql";
            $package = "backup-" . $signature . ".zip";
        } else {
            $database = collect(Storage::allFiles('backup'))->filter(fn($f)=>Str::contains($f, '.sql'))->map(fn($f)=>Str::replace('backup/', '', $f))->last();
            $package = collect(Storage::allFiles('backup'))->filter(fn($f)=>Str::contains($f, '.zip'))->map(fn($f)=>Str::replace('backup/', '', $f))->last();
        }

        $signature = $signature ?? collect(Storage::allFiles('backup'))->map(fn($f)=>Str::of($f)->substr(0, -4)->replace(['backup','/-'], ''))->last();

        $this->info(Str::of(env('APP_URL'))->trim('/http://https://') . " Is being restored.");
        SlackAlert::message(Str::of(env('APP_URL'))->trim('/http://https://') . " Is being restored.");

        $canData = false;
        $canPack = false;
        if (file_exists($path = $backupPath . $database)) {
            $sql = file_get_contents($path);
            DB::unprepared($sql);
            $canData = true;
        }
        if (file_exists($path = $backupPath . $package)) {
            $zip = new Madzipper;
            $zip->make($backupPath . $package)->extractTo(storage_path("app"));
            if ($delete) {
                unlink($backupPath . $database);
                unlink($backupPath . $package);
                $this->info("backup signature $signature deleted.");
            }
            $canPack = true;
        }

        if ($canPack || $canData) {
            $this->info("System has been restored to $signature backup signature.");
            return 0;
        }

        $this->error("System restore failed, no backup available.");
        return 1;
    }

    /**
     * Reset the system to default
     *
     * @param boolean $backup
     * @return integer
     */
    protected function reset($backup = false): int
    {
        $this->info(Str::of(env('APP_URL'))->trim('/http://https://') . " Is being reset.");
        SlackAlert::message(Str::of(env('APP_URL'))->trim('/http://https://') . " Is being reset.");

        // Backup the system
        if ($backup) {
            $this->backup();
        }

        SlackAlert::message("System reset started at: ". Carbon::now());
        $this->info("System reset started.");

        // Delete public Symbolic links
        file_exists(public_path('media')) && unlink(public_path('media'));
        file_exists(public_path('storage')) && unlink(public_path('storage'));
        file_exists(public_path('uploads')) && unlink(public_path('uploads'));
        // Create public Symbolic links
        Artisan::call('storage:link');
        $this->info("Public Symbolic links deleted.");

        // Delete User, Transaction, Subscription, Order and Saving
        User::with(['transactions', 'subscription'])->get()->map(function($user) {
            // Delete Transactions
            if ($user->transactions) {
                $user->transactions->map(function($transaction) {
                    if ($transaction->transactable) {
                        // $transaction->transactable->delete();
                    }
                    // $transaction->delete();
                });
            }
            if ($user->subscriptions) {
                $user->subscriptions->map(function($subscription) {
                    // $subscription->delete();
                });
            }
            $user->image && Storage::delete($user->image??'');
            // dd($user);
        });

        // Delete Plan and FoodBag
        Plan::with(['bags'])->get()->map(function($plan) {
            if ($plan->bags) {
                $plan->bags->map(function($bag) {
                    // $bag->delete();
                });
            }
            $plan->image && Storage::delete($plan->image??'');
            // dd($plan);
        });

        // Delete FruitBayCategory and FruitBay
        FruitBayCategory::with(['items'])->get()->map(function($fruitbaycat) {
            if ($fruitbaycat->items) {
                $fruitbaycat->items->map(function($item) {
                    $item->image && Storage::delete($item->image??'');
                    // $item->delete();
                });
            }
            $fruitbaycat->image && Storage::delete($fruitbaycat->image??'');
            // dd($fruitbaycat);
        });

        // Delete FrontContent
        // FrontContent::get()->map(function($content) {
            // $content->image && Storage::delete($content->image??'');
            // dd($content);
        // });

        // Delete Food
        Food::get()->map(function($food) {
            $food->image && Storage::delete($food->image??'');
            // dd($food);
        });
        if (Artisan::call('migrate:refresh') === 0) {
            if (Artisan::call('db:seed') === 0) {
                SlackAlert::message("System reset completed at: ". Carbon::now());
                $this->info("System reset completed successfully.");
                return 0;
            }
        }
        SlackAlert::message("An error occured at: ". Carbon::now() . ". Unable to complete system reset.");
        $this->error("An error occured.");
        return 1;
    }
}
