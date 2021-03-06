<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\SettingsModel;
use App\Services\HelpersFncService;
use App\Services\SettingsService;
use App\Services\SystemLogService;
use App\Traits\Language;
use Axdlee\Config\Rewrite;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use View;
use Validator;
use Config;

class SettingsController extends Controller
{
    use Language;

    private $systemLogs;
    private $settingsModel;
    private $settingsService;
    private $helpersService;

    public function __construct()
    {
        $this->systemLogs = new SystemLogService();
        $this->settingsModel = new SettingsModel();
        $this->settingsService = new SettingsService();
        $this->helpersService = new HelpersFncService();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = config('crm_settings.temp');

        return view('crm.settings.index')->with([
            'input' => $input,
            'logs' => $this->helpersService->formatAllSystemLogs()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $getAllInputFromRequest = Input::all();

        $validator = Validator::make($getAllInputFromRequest, $this->settingsService->loadRules());

        if($validator->fails()) {
            return Redirect::back()->with('message_danger', $this->getMessage('messages.ErrorSettingsStore'));
        }

        $writeConfig = new Rewrite;
        $writeConfig->toFile(base_path() . '/config/crm_settings.php', [
            'pagination_size' => $getAllInputFromRequest['pagination_size'],
            'currency' => $getAllInputFromRequest['currency'],
            'priority_size' => $getAllInputFromRequest['priority_size'],
            'invoice_tax' => $getAllInputFromRequest['invoice_tax'],
            'invoice_logo_link' => $getAllInputFromRequest['invoice_logo_link'],
            'rollbar_token' => $getAllInputFromRequest['rollbar_token'],
            'loading_circle' => $getAllInputFromRequest['loading_circle'],
            'stats' => $getAllInputFromRequest['stats']
        ]);

        $this->settingsService->saveEnvData($getAllInputFromRequest['rollbar_token']);

        $this->systemLogs->insertSystemLogs('SettingsModel has been changed.', $this->systemLogs::successCode);

        return Redirect::back()->with('message_success', $this->getMessage('messages.SuccessSettingsUpdate'));
    }
}
