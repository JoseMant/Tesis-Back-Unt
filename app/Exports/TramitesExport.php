<?php

namespace App\Exports;

use App\Tramite;
use Maatwebsite\Excel\Concerns\FromCollection;

class TramitesExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Tramite::all();
    }
}
