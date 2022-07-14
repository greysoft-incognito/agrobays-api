<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Carbon\CarbonImmutable as Carbon;
use Illuminate\Console\Command;
use Yabacon\Paystack;
use Yabacon\Paystack\Exception\ApiException;

/**
 * Handle transactions
 * Can list and clear local transactions as well as transactions
 * referenced by the specified payment gateway
 */
class HandleTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "transactions
                            {status=success : Filter transactions by status paystack('failed', 'success', 'abandoned'), local('pending','complete','rejected')}
                            {--f|from= : A timestamp from which to start listing transactions e.g. 2016-09-24T00:00:05.000Z, 2016-09-21}
                            {--t|to= : A timestamp to which to end listing transactions e.g. 2016-09-30T00:00:05.000Z, 2016-09-30}
                            {--r|persistent : Determine wether to load all available pages iteratively or just return the first page}
                            {--a|action=list : An action to perform on the transaction ('list', 'clear')}
                            {--p|perpage=50 : Specify how many records you want to retrieve per page. Default value is 50.}
                            {--o|offset=1 : Specify exactly what page you want to retrieve. If not specify we use a default value of 1.}
                            {--s|source=paystack : The source of the transactions to shoe ('local', 'paystack')}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List and Manage transactions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $status = $this->argument('status');
        $persistent = $this->option('persistent');
        $perpage = $this->option('perpage');
        $offset = $this->option('offset');
        $source = $this->option('source');
        $action = $this->option('action');
        $from = $this->option('from');
        $to = $this->option('to');

        // Handle paystack requests
        if ($source === 'paystack') {
            try {
                $params = [
                    'perPage' => $perpage,
                    'offset' => $offset,
                    'status' => $status,
                    'from' => $from,
                    'to' => $to,
                ];
                $transactions = $this->loadPaystackTransactions($params);
                $tranx = collect($transactions->data);

                // Progrmatically fetch more records
                if ($persistent === true) {
                    $page = $transactions->meta->page + 1;
                    $_tranx = [];
                    do {
                        $_tranx[] = $this->loadPaystackTransactions($params, $page)->data;
                        $page++;
                    } while ($page <= $transactions->meta->pageCount);

                    $tranx = $tranx->merge($_tranx)->flatten();
                }

                // Clear transactions
                if ($action === 'clear') {
                    $count = $tranx->filter(fn ($tranx) =>Transaction::whereReference($tranx->reference)->exists())
                    ->each(function ($tranx) {
                        if ($transaction = Transaction::whereReference($tranx->reference)->with(['transactable'])->first()) {
                            if ($transaction->transactable) {
                                $transaction->transactable->delete();
                            }
                            $transaction->delete();
                        }
                    })->count();
                    $this->info($count." $status transactions cleared.");
                // List transactions
                } elseif ($action === 'list') {
                    $this->info($transactions->message);
                    $this->table(
                        ['Status', 'Reference', 'Amount', 'Paid At', 'Created At', 'Channel', 'Currency'],
                        $tranx->map(fn ($d) =>collect($d)->only(['status', 'reference', 'amount', 'created_at', 'paid_at', 'channel', 'currency'])->all())
                            ->sortKeys()
                    );
                }
            } catch (ApiException $e) {
                $this->error($e->getMessage());
            }
            // Handle local request
        } elseif ($source === 'local') {
            // Prepare transactions
            $transactions = $this->loadLocalTransactions($status, $perpage, $from, $to, $offset);
            $tranx = $transactions->items();

            // Progrmatically fetch more records
            if ($persistent === true) {
                $page = $transactions->currentPage() + 1;
                $_tranx = [];
                do {
                    $_tranx[] = $this->loadLocalTransactions($status, $perpage, $from, $to, $page)->items();
                    $page++;
                } while ($page <= $transactions->total());
                $tranx = collect($tranx)->merge($_tranx)->flatten();
            }

            // Clear transactions
            if ($action === 'clear') {
                $count = $tranx->each(function ($transaction) {
                    if ($transaction->transactable) {
                        $transaction->transactable->delete();
                    }
                    $transaction->delete();
                });
                $this->info("All $status transactions cleared.");
            // List transactions
            } elseif ($action === 'list') {
                $this->info('Transactions collected successfully');
                $this->table(
                    ['Type', 'Reference', 'Method', 'Amount', 'Created At', 'Status'],
                    $tranx->map(fn ($d) =>$d->only(['transactable_type', 'reference', 'method', 'amount', 'created_at', 'status']))->all()
                );
            }
        }

        return 0;
    }

    /**
     * Fetch paystack transactions
     *
     * @param  array  $params
     * @param  int  $page
     * @return Yabacon\Paystack\Contracts\RouteInterface
     */
    private function loadPaystackTransactions($params, $page = 1)
    {
        $paystack = new Paystack(env('PAYSTACK_SECRET_KEY'));

        return $paystack->transaction->getList(array_merge($params, ['page' => $page]));
    }

    /**
     * Fetch local transactions
     *
     * @param  string  $status
     * @param  int  $perpage
     * @param  int  $offset
     * @param  string  $from
     * @param  string  $to
     * @param  int  $page
     * @return Illuminate\Database\Eloquent\Model
     */
    private function loadLocalTransactions($status, $perpage, $from, $to, $page = 1)
    {
        $transactions = Transaction::where('status', $status)->with(['transactable'])->paginate($perpage, ['*'], 'page', $page);
        if ($from) {
            $from = new Carbon($from);
            if (! $to) {
                $transactions->whereBetween('created_at', [$from, Carbon::parse('NOW')]);
            }
        }
        if ($to) {
            $to = new Carbon($to);
            if (! $from) {
                $transactions->whereBetween('created_at', [$to, Carbon::parse('NOW')]);
            }
        }
        if ($to && $from) {
            $transactions->whereBetween('created_at', [$from, $to]);
        }

        return $transactions;
    }
}
