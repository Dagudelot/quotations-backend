<?php

namespace Actions\SheetsActions;

use Config\Environment;
use Helpers\Months;
use Services\SheetsService;

class CreateSheetAction
{
    use Environment, Months;

    const START_ITEMS_ROW_INDEX = 10;
    const START_ITEMS_COLUMN_INDEX = 0;

    /**
     * @var SheetsService
     */
    private $sheetsService;
    /**
     * @var \Google_Service_Sheets
     */
    private $service;
    private $data;
    private $spreadsheet;
    private $rowsCount;
    private $columnsCount;
    private $toCount;

    public function __construct(SheetsService $sheetsService, array $data )
    {
        $this->sheetsService = $sheetsService;
        $this->data = $data;
        $this->rowsCount = count($data['items']);
        $this->columnsCount = count($data['items'][0]);
        $this->toCount = count($data['to']);
    }

    public function __invoke(): \Google_Service_Sheets_Spreadsheet
    {
        return $this->createSheet();
    }

    private function createSheet(): ?\Google_Service_Sheets_Spreadsheet
    {
        // Get the API client and construct the service object.
        $client = $this->sheetsService->getClient();

        if( $client ){
            $this->service = new \Google_Service_Sheets($client);

            $spreadsheet = new \Google_Service_Sheets_Spreadsheet([
                'properties' => [
                    'title' => "COTIZACIÓN " . strtoupper($this->data['title'])
                ]
            ]);

            $this->spreadsheet = @$this->service->spreadsheets->create($spreadsheet);

            $this->setHeaders()
                ->setItems()
                ->setTotal()
                ->format();

            return $this->spreadsheet;
        }

        return null;
    }

    private function setHeaders(): CreateSheetAction
    {
        $this->setDate()
            ->setUserInformation()
            ->setSector()
            ->setTo()
            ->setColumnsTitles();

        return $this;
    }

    private function setItems(): CreateSheetAction
    {
        $range = "A".(self::START_ITEMS_ROW_INDEX+$this->toCount+1).":D".(self::START_ITEMS_ROW_INDEX+$this->toCount+1);
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => $this->data['items']
        ]);
        $params = [
            'valueInputOption' => "RAW"
        ];

        @$this->service->spreadsheets_values->append(
            $this->spreadsheet->spreadsheetId,
            $range,
            $body,
            $params
        );

        return $this;
    }

    private function setTotal(): CreateSheetAction
    {
        $startOfItemsIndex = self::START_ITEMS_ROW_INDEX+$this->toCount+1;
        $body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => $startOfItemsIndex-1,
                        "endRowIndex" => $startOfItemsIndex+$this->rowsCount-1,
                        "startColumnIndex" => 4,
                        "endColumnIndex" => 5
                    ],
                    "cell" => [
                        "userEnteredValue" => [
                            "formulaValue" => "=B".$startOfItemsIndex."*D".$startOfItemsIndex
                        ]
                    ],
                    "fields" => "userEnteredValue"
                ],
            ]
        ]);
        $body2 = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount+$this->rowsCount,
                        "endRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount+$this->rowsCount+1,
                        "startColumnIndex" => 4,
                        "endColumnIndex" => 5
                    ],
                    "cell" => [
                        "userEnteredValue" => [
                            "formulaValue" => "=SUM(E".($startOfItemsIndex).":E".($startOfItemsIndex+$this->rowsCount-1).")"
                        ]
                    ],
                    "fields" => "userEnteredValue"
                ],
            ]
        ]);
        $body3 = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount+$this->rowsCount,
                        "endRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount+$this->rowsCount+1,
                        "startColumnIndex" => 3,
                        "endColumnIndex" => 5
                    ],
                    "cell" => [
                        "userEnteredFormat" => [
                            "textFormat" => [
                                "foregroundColor" => [
                                    "red" => 255.0,
                                    "green" => 255.0,
                                    "blue" => 255.0
                                ],
                                "bold" => true
                            ]
                        ]
                    ],
                    "fields" => "userEnteredFormat.textFormat"
                ]
            ]
        ]);

        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body
        );
        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body2
        );
        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body3
        );
        @$this->service->spreadsheets_values->update(
            $this->spreadsheet->spreadsheetId,
            "D".(self::START_ITEMS_ROW_INDEX+$this->toCount+$this->rowsCount+1),
            new \Google_Service_Sheets_ValueRange([
                'values' => [
                    [
                        "TOTAL"
                    ]
                ]
            ]),
            [
                'valueInputOption' => "RAW"
            ]
        );

        return $this;
    }

    private function format(): void
    {
        $this->formatCurrencyColumns()
            ->hideGridLines()
            ->align()
            ->setBorders()
            ->formatBackgrounds()
            ->autoResizeColumns();
    }

    private function setDate(): CreateSheetAction
    {
        $dateArray = explode('-', $this->data['date']);
        $formattedDate = "Medellín, ".$dateArray[2]." de ".$this->getMonth($dateArray[1])." del ".$dateArray[0];
        $range = "A1";
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => [
                [
                    $formattedDate
                ]
            ]
        ]);
        $params = [
            'valueInputOption' => "RAW"
        ];

        @$this->service->spreadsheets_values->update(
            $this->spreadsheet->spreadsheetId,
            $range,
            $body,
            $params
        );

        return $this;
    }

    private function setUserInformation(): CreateSheetAction
    {
        $name = strtoupper( $this->env()['user_name'] );
        $range = "A3:B3";
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => [
                [
                    "Contratista",
                    $name,
                ]
            ]
        ]);
        $params = [
            'valueInputOption' => "RAW"
        ];

        @$this->service->spreadsheets_values->append(
            $this->spreadsheet->spreadsheetId,
            $range,
            $body,
            $params
        );

        return $this;
    }

    private function setSector(): CreateSheetAction
    {
        $range = "A5:B5";
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => $this->data['sector']
        ]);
        $params = [
            'valueInputOption' => "RAW"
        ];

        @$this->service->spreadsheets_values->append(
            $this->spreadsheet->spreadsheetId,
            $range,
            $body,
            $params
        );

        return $this;
    }

    private function setTo(): CreateSheetAction
    {
        $range = "A8:B8";
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => $this->data['to']
        ]);
        $params = [
            'valueInputOption' => "RAW"
        ];

        @$this->service->spreadsheets_values->append(
            $this->spreadsheet->spreadsheetId,
            $range,
            $body,
            $params
        );

        return $this;
    }

    private function setColumnsTitles(): CreateSheetAction
    {
        $range = "A".(self::START_ITEMS_ROW_INDEX+$this->toCount).":E".(self::START_ITEMS_ROW_INDEX+$this->toCount);
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => [
                [
                    'ITEM',
                    'CANTIDAD',
                    'UNIDAD DE MEDIDA',
                    'VALOR UNITARIO',
                    'VALOR TOTAL'
                ]
            ]
        ]);
        $params = [
            'valueInputOption' => "RAW"
        ];

        @$this->service->spreadsheets_values->update(
            $this->spreadsheet->spreadsheetId,
            $range,
            $body,
            $params
        );

        return $this;
    }

    private function autoResizeColumns(): CreateSheetAction
    {
        $body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "autoResizeDimensions" => [
                    "dimensions" => [
                        "dimension" => "COLUMNS",
                        "startIndex" => 0,
                        "endIndex" => 5
                    ]
                ]
            ]
        ]);

        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body
        );

        return $this;
    }

    private function hideGridLines(): CreateSheetAction
    {
        $body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "updateSheetProperties" => [
                    "fields" => "gridProperties(hideGridlines)",
                    "properties" => [
                        "sheetId" => 0,
                        "gridProperties" => [
                            "hideGridlines" => $this->data['hide_grid_lines']
                        ]
                    ]
                ]
            ]
        ]);

        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body
        );

        return $this;
    }

    private function align(): CreateSheetAction
    {
        $body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => 0,
                        "endRowIndex" => 100,
                        "startColumnIndex" => 1,
                        "endColumnIndex" => 5
                    ],
                    "cell" => [
                        "userEnteredFormat" => [
                            "horizontalAlignment"  => "RIGHT",
                        ]
                    ],
                    "fields" => "userEnteredFormat.horizontalAlignment"
                ]
            ]
        ]);

        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body
        );

        return $this;
    }

    private function setBorders(): CreateSheetAction
    {
        $body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "updateBorders" => [
                    "range" => [
                      "startRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount,
                      "endRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount+$this->rowsCount+1,
                      "startColumnIndex" => 0,
                      "endColumnIndex" => 5
                    ],
                    "top" => [
                        "style" => "SOLID",
                        "width" => 1,
                        "color" => [
                            "red" => 1.0,
                            "green" => 1.0,
                            "blue" => 1.0
                        ],
                    ],
                    "bottom" => [
                        "style" => "SOLID",
                        "width" => 1,
                        "color" => [
                            "red" => 1.0,
                            "green" => 1.0,
                            "blue" => 1.0
                        ],
                    ],
                    "innerHorizontal" => [
                        "style" => "SOLID",
                        "width" => 1,
                        "color" => [
                            "red" => 1.0,
                            "green" => 1.0,
                            "blue" => 1.0
                        ],
                    ],
                ]
            ]
        ]);

        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body
        );

        return $this;
    }

    private function formatBackgrounds(): CreateSheetAction
    {
        $body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => 4,
                        "endRowIndex" => 6,
                        "startColumnIndex" => 0,
                        "endColumnIndex" => 1
                    ],
                    "cell" => [
                        "userEnteredFormat" => [
                            "backgroundColor" => [
                                "red" => 100.0,
                                "green" => 100.0,
                                "blue" => 100.0
                            ],
                            "textFormat" => [
                                "foregroundColor" => [
                                    "red" => 1.0,
                                    "green" => 1.0,
                                    "blue" => 1.0
                                ],
                                "bold" => true
                            ]
                        ]
                    ],
                    "fields" => "userEnteredFormat(backgroundColor,textFormat)"
                ]
            ]
        ]);
        $body2 = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => 7,
                        "endRowIndex" => 8,
                        "startColumnIndex" => 0,
                        "endColumnIndex" => 1
                    ],
                    "cell" => [
                        "userEnteredFormat" => [
                            "backgroundColor" => [
                                "red" => 100.0,
                                "green" => 100.0,
                                "blue" => 100.0
                            ],
                            "textFormat" => [
                                "foregroundColor" => [
                                    "red" => 1.0,
                                    "green" => 1.0,
                                    "blue" => 1.0
                                ],
                                "bold" => true
                            ]
                        ]
                    ],
                    "fields" => "userEnteredFormat(backgroundColor,textFormat)"
                ]
            ]
        ]);
        $body2 = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => 7,
                        "endRowIndex" => 8,
                        "startColumnIndex" => 0,
                        "endColumnIndex" => 1
                    ],
                    "cell" => [
                        "userEnteredFormat" => [
                            "backgroundColor" => [
                                "red" => 100.0,
                                "green" => 100.0,
                                "blue" => 100.0
                            ],
                            "textFormat" => [
                                "foregroundColor" => [
                                    "red" => 1.0,
                                    "green" => 1.0,
                                    "blue" => 1.0
                                ],
                                "bold" => true
                            ]
                        ]
                    ],
                    "fields" => "userEnteredFormat(backgroundColor,textFormat)"
                ]
            ]
        ]);
        $body3 = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => 2,
                        "endRowIndex" => 3,
                        "startColumnIndex" => 0,
                        "endColumnIndex" => 1
                    ],
                    "cell" => [
                        "userEnteredFormat" => [
                            "backgroundColor" => [
                                "red" => 100.0,
                                "green" => 100.0,
                                "blue" => 100.0
                            ],
                            "textFormat" => [
                                "foregroundColor" => [
                                    "red" => 1.0,
                                    "green" => 1.0,
                                    "blue" => 1.0
                                ],
                                "bold" => true
                            ]
                        ]
                    ],
                    "fields" => "userEnteredFormat(backgroundColor,textFormat)"
                ]
            ]
        ]);
        $body4 = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount-1,
                        "endRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount,
                        "startColumnIndex" => self::START_ITEMS_COLUMN_INDEX,
                        "endColumnIndex" => self::START_ITEMS_COLUMN_INDEX+$this->columnsCount+1
                    ],
                    "cell" => [
                        "userEnteredFormat" => [
                            "backgroundColor" => [
                                "red" => 100.0,
                                "green" => 100.0,
                                "blue" => 100.0
                            ],
                            "horizontalAlignment"  => "CENTER",
                            "textFormat" => [
                                "foregroundColor" => [
                                    "red" => 1.0,
                                    "green" => 1.0,
                                    "blue" => 1.0
                                ],
                                //"fontSize" => 12,
                                "bold" => true
                            ]
                        ]
                    ],
                    "fields" => "userEnteredFormat(backgroundColor,textFormat,horizontalAlignment)"
                ]
            ]
        ]);

        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body
        );
        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body2
        );
        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body3
        );
        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body4
        );

        return $this;
    }

    private function formatCurrencyColumns(): CreateSheetAction
    {
        $body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            "requests" => [
                "repeatCell" => [
                    "range" => [
                        "startRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount,
                        "endRowIndex" => self::START_ITEMS_ROW_INDEX+$this->toCount+$this->rowsCount,
                        "startColumnIndex" => 3,
                        "endColumnIndex" => 5
                    ],
                    "cell" => [
                        "userEnteredFormat" => [
                            "numberFormat" => [
                                "type" => "CURRENCY",
                                "pattern" => "\"$\"#,00"
                            ]
                        ]
                    ],
                    "fields" => "userEnteredFormat.numberFormat"
                ]
            ]
        ]);

        @$this->service->spreadsheets->batchUpdate(
            $this->spreadsheet->spreadsheetId,
            $body
        );

        return $this;
    }
}