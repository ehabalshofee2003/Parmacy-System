<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateDailyStockReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-daily-stock-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
 public function handle()
{
    app(\App\Services\Reports\StockReportService::class)->generate('daily');
    $this->info('تم إنشاء تقرير المخزون اليومي.');
}


}
