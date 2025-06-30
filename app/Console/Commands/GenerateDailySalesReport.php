<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateDailySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-daily-sales-report';

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
    // نولد تقرير مبيعات يومي
    app(\App\Services\Reports\SalesReportService::class)
        ->generate('daily');

    $this->info('Daily Sales Report generated successfully.');
  }

}
