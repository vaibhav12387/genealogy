<?php

namespace App\Http\Controllers\Gedcom;

use App\Http\Controllers\Controller;
use App\Jobs\ExportGedCom;
use App\Models\Family;
use App\Models\Person;
use FamilyTree365\LaravelGedcom\Utils\GedcomGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\TenantConnectionResolver;

class Export extends Controller
{
    use TenantConnectionResolver;

    public function __invoke(Request $request)
    {

        $ts = microtime(true);
        $file = env('APP_NAME').date('_Ymd_').$ts.'.ged';
        $file = str_replace(' ', '', $file);
        $file = str_replace("'", '', $file);

        ExportGedCom::dispatch($file, $request->user());

        Log::info('Read gedfile from '.\Storage::disk('public')->path($file));
        // var_dump(\Storage::disk("public")->path($file), "controller");
        return json_encode([
	    'file' => \Storage::disk('public')->get($file),
//	    'file' => file_get_contents('/home/genealogia/domains/api.genealogia.co.uk/genealogy/storage/app/gedcom/' . $file, true),
//	    'file' => file_get_contents('/storage/app/gedcom/' . $file, true),
            'name' => $file,
        ]);
    }
}
