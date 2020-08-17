<?php

namespace Controllers;

use Actions\SheetsActions\CreateSheetAction;
use Config\Environment;
use ObjectModels\Item;
use ObjectModels\Sector;
use ObjectModels\To;
use Services\SheetsService;

class QuotationsController
{
    use Environment;

    public function create()
    {
        // Retrieve Request
        $date = $_POST['date'];                 // Format: YYYY-mm-dd
        $title = $_POST['title'];
        $sector = $_POST['sector'];
        $to = $_POST['to'];
        $items = $_POST['items'];
        $unit_values = $_POST['unit_values'];
        $measurement_units = $_POST['measurement_units'];
        $quantities = $_POST['quantities'];
        $hide_grid_lines = $_POST['hide_grid_lines'] == "on";

        // Format Data
        $itemsObjectModels = new Item([
            'items'             => $items,
            'quantities'        => $quantities,
            'measurement_units' => $measurement_units,
            'unit_values'       => $unit_values
        ]);
        $sectorObjectModel = new Sector([
            'title'     => $title,
            'sector'    => $sector
        ]);
        $toObjectModel = new To([
            'to'    => $to
        ]);

        $formattedItems = $itemsObjectModels->get();
        $formattedSector = $sectorObjectModel->get();
        $formattedTo = $toObjectModel->get();

        // Data
        $data = [
            'date'              => $date,
            'title'             => $title,
            'sector'            => $formattedSector,
            'to'                => $formattedTo,
            'items'             => $formattedItems,
            'hide_grid_lines'   => $hide_grid_lines
        ];

        // Instance Sheets Service
        $sheetsService = new SheetsService();

        // Create Sheet
        $createSheet = new CreateSheetAction($sheetsService, $data);
        $sheet = $createSheet();
        $sheetUrl = $sheet->spreadsheetUrl;

        // Return view
        $return = $this->env()['frontend_url'] . "success.html";
        header("location:".$return);
        die();
    }
}