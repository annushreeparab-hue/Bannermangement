<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\CronLog;

class CrawlDataCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:crawl-data';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Import documents from thedealappmongo into crawled_data collection';
    /**
     * Execute the console command.
     */

    // public function handle()
    // {
    //     $cron_log = [];
    //     $cron_log['cronName'] = "ImportCrawledData";
    //     $cron_log['startAt'] = date('d-m-y h:i:s');
    //     $create_cron_log = CronLog::createCronLog($cron_log);
    //     $id = $create_cron_log['_id'];

    //     $this->info("Starting selective data import...");

    //     // 1. Fetch only unpushed documents
    //     $documents = DB::connection('crawl_source')
    //                     ->collection('dynamic_Scrapers')
    //                     ->where(function ($query) {
    //                         $query->whereNull('is_data_pushed_to_climakosh')
    //                             ->orWhere('is_data_pushed_to_climakosh', 0);
    //                     })
    //                     ->get()
    //                     ->toArray();

    //     foreach ($documents as $doc) {
    //         $data = $doc;

    //         // 2. Add is_verified = 0 in destination
    //         $data['is_verified'] = 0;

    //         // 3. Parse all tabular string fields
    //         foreach ($doc as $key => $value) {
    //             if (is_string($value) && str_contains($value, "\t") && str_contains($value, "\n")) {
    //                 try {
    //                     $lines = explode("\n", $value);
    //                     $headers = explode("\t", array_shift($lines));
    //                     $parsedData = [];

    //                     foreach ($lines as $line) {
    //                         $values = explode("\t", $line);
    //                         $row = [];

    //                         foreach ($headers as $i => $header) {
    //                             $val = $values[$i] ?? null;
    //                             $row[$header] = is_numeric($val) ? (int)$val : $val;
    //                         }

    //                         $parsedData[] = $row;
    //                     }

    //                     // Store parsed data under a new key
    //                     $data['parsed_' . strtolower($key)] = $parsedData;

    //                 } catch (\Exception $e) {
    //                     \Log::warning("Failed to parse field [$key] for ID {$data['_id']} - " . $e->getMessage());
    //                 }
    //             }
    //         }

    //         try {
    //             // 4. Insert into destination DB
    //             DB::connection('mongodb')->collection('crawled_data')->insert($data);

    //             // 5. Update source to mark as pushed
    //             DB::connection('crawl_source')
    //                 ->collection('dynamic_Scrapers')
    //                 ->where('_id', $data['_id'])
    //                 ->update(['is_data_pushed_to_climakosh' => 1]);

    //         } catch (\Exception $e) {
    //             \Log::warning("Insert failed for ID {$data['_id']} - " . $e->getMessage());
    //         }
    //     }

    //     $this->info("Selective data import completed.");

    //     $cron_log_update['endAt'] = date('d-m-y h:i:s');
    //     CronLog::updateCronLog($id, $cron_log_update);
    // }


    public function handle()
    {
        $cron_log = [];
        $cron_log['cronName'] = "ImportCrawledData";
        $cron_log['startAt'] = date('d-m-y h:i:s');
        $create_cron_log = CronLog::createCronLog($cron_log);
        $id = $create_cron_log['_id'];

        $this->info("Starting selective data import...");

        // 1. Fetch only unpushed documents
        $documents = DB::connection('crawl_source')
            ->collection('dynamic_Scrapers')
            ->where(function ($query) {
                $query->whereNull('is_data_pushed_to_climakosh')
                    ->orWhere('is_data_pushed_to_climakosh', 0);
            })
            ->get()
            ->toArray();

        foreach ($documents as $doc) {
            $data = $doc;
            $data['is_verified'] = 0;

            foreach ($doc as $key => $value) {
                if (!is_string($value)) continue;

                // Tabular data (with tabs and newlines)
                if (str_contains($value, "\t") && str_contains($value, "\n")) {
                    try {
                        $lines = explode("\n", $value);
                        $headers = explode("\t", array_shift($lines));
                        $parsedData = [];

                        foreach ($lines as $line) {
                            $values = explode("\t", $line);
                            $row = [];

                            foreach ($headers as $i => $header) {
                                $val = $values[$i] ?? null;
                                $row[$header] = is_numeric($val) ? (float)$val : $val;
                            }

                            $parsedData[] = $row;
                        }

                        $data['parsed_' . strtolower($key)] = $parsedData;
                    } catch (\Exception $e) {
                        \Log::warning("Failed to parse tabular field [$key] for ID {$data['_id']} - " . $e->getMessage());
                    }
                }

                // Custom format: "Demand", "Frequency", etc.
                elseif (in_array($key, ['Demand', 'Frequency']) && str_contains($value, "\n")) {
                    try {
                        $lines = array_values(array_filter(explode("\n", $value)));
                        $label = $lines[0] ?? $key;
                        $date = $lines[1] ?? null;
                        $peak = $lines[2] ?? null;
                        $unit = null;
                        $time_series = [];

                        $nonDataLines = [$label, $date, $peak];
                        $remaining = array_values(array_diff($lines, $nonDataLines));

                        // Handle unit/footer cleanup
                        if (end($remaining) === 'TIME (Hrs)') array_pop($remaining);
                        if (preg_match('/\((.*?)\)/', end($remaining), $match)) {
                            $unit = trim($match[0], '()');
                            array_pop($remaining);
                        }

                        $times = [];
                        $values = [];

                        foreach ($remaining as $item) {
                            if (preg_match('/^\d{2}:\d{2}/', $item)) {
                                $times[] = $item;
                            } elseif (is_numeric($item)) {
                                $values[] = (float)$item;
                            }
                        }

                        foreach ($times as $i => $t) {
                            $time_series[] = [
                                'time' => $t,
                                'value' => $values[$i] ?? null
                            ];
                        }

                        $data['parsed_' . strtolower($key)] = [
                            'label' => $label,
                            'date' => $date,
                            'peak' => $peak,
                            'unit' => $unit,
                            'time_series' => $time_series
                        ];
                    } catch (\Exception $e) {
                        \Log::warning("Failed to parse [$key] for ID {$data['_id']} - " . $e->getMessage());
                    }
                }

                // Fallback parser for newline-separated key-value/unit data
                elseif (str_contains($value, "\n")) {
                    try {
                        $lines = array_values(array_filter(array_map('trim', explode("\n", $value))));
                        $structured = [];
                        $i = 0;

                        while ($i < count($lines)) {
                            $value = $lines[$i] ?? null;
                            $unit = $lines[$i + 1] ?? null;
                            $label = $lines[$i + 2] ?? null;

                            // Example pattern: value, unit, label
                            if (is_numeric($value) && $unit && $label) {
                                $structured[] = [
                                    'label' => $label,
                                    'unit' => $unit,
                                    'value' => is_numeric($value) ? (float)$value : $value
                                ];
                                $i += 3;
                            }
                            // Handle ending lines like "Last Updated"
                            elseif (str_starts_with(strtolower($value), 'last updated')) {
                                $structured[] = ['info' => $value];
                                $i++;
                            } else {
                                $i++;
                            }
                        }

                        if (!empty($structured)) {
                            $data['parsed_' . strtolower($key)] = $structured;
                        }

                    } catch (\Exception $e) {
                        \Log::warning("Failed to parse structured field [$key] for ID {$data['_id']} - " . $e->getMessage());
                    }
                }

            }

            try {
                // 4. Insert into destination DB
                DB::connection('mongodb')->collection('crawl_data')->insert($data);

                // 5. Update source to mark as pushed
                DB::connection('crawl_source')
                    ->collection('dynamic_Scrapers')
                    ->where('_id', $data['_id'])
                    ->update(['is_data_pushed_to_climakosh' => 1]);

            } catch (\Exception $e) {
                \Log::warning("Insert failed for ID {$data['_id']} - " . $e->getMessage());
            }
        }

        $this->info("Selective data import completed.");

        $cron_log_update['endAt'] = date('d-m-y h:i:s');
        CronLog::updateCronLog($id, $cron_log_update);
    }

}
