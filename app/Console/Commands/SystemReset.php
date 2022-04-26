<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Madnest\Madzipper\Madzipper;
use Illuminate\Support\Facades\Artisan;

use App\Models\Food;
use App\Models\FrontContent;
use App\Models\FruitBay;
use App\Models\FruitBayCategory;
use App\Models\FoodBag;
use App\Models\Plan;
use App\Models\Order;
use App\Models\Saving;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;

class SystemReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:reset
                            {--r|restore : restore the last backup, Provide a backup signature value to restore a particular backup. E.g. 2022-04-26_16-05-34.}
                            {--b|backup : Do a complete system backup before the reset.}';

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
        $backup = $this->option('backup');
        $restore = $this->option('restore');

        if ($restore) {
            $last = collect(Storage::allFiles('backup'))->filter(fn($f)=>\Str::contains($f, '.sql'))->last();
            $zip = new \Zipper;
            $zip->getFileContent('storage/app/'.$last)->extractTo('');
            $signature = ($restore === true) ? 'the last available backup' : $restore . ' backup signature';
            $this->info("System has been restored to $signature.");
            return 0;
        }

        if ($backup) {
            $filename = "backup-" . \Carbon\Carbon::now()->format('Y-m-d_H-i-s');
            $storageAt = storage_path() . "/app/backup/";
            $command = "mysqldump --skip-comments"
            ." --user=" . env('DB_USERNAME')
            ." --password=" . env('DB_PASSWORD')
            . " --host=" . env('DB_HOST')
            . " " . env('DB_DATABASE') . " > " . $storageAt . $filename . ".sql";
            $returnVar = NULL; $output = NULL;
            exec($command, $output, $returnVar);

            $zip = new Madzipper;
            $zip->make($storageAt . $filename . '.zip')->folder('storage/public/media')->add(Storage::allFiles('public/media'));
            $zip->folder('storage/public/uploads')->add(Storage::allFiles('public/uploads'));
        }

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
                $this->info("System reset completed successfully.");
                return 0;
            }
        }
        $this->error("An error occured.");
        return 0;
    }
}