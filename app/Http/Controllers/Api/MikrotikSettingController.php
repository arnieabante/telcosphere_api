<?php

namespace App\Http\Controllers\Api;

use App\Models\MikrotikLog;
use App\Models\MikrotikSetting;
use Illuminate\Http\Request;

class MikrotikSettingController extends ApiController
{
    public function show()
    {
        return response()->json(['data' => $this->present(MikrotikSetting::forSite(auth()->user()->site_id))]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'isEnabled' => 'required|boolean',
            'dryRun' => 'required|boolean',
            'disconnectWindowStart' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'disconnectWindowEnd' => ['required', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'maxSuspendPercent' => 'required|integer|min:1|max:100',
            'timezone' => 'required|timezone',
        ]);

        $settings = MikrotikSetting::forSite(auth()->user()->site_id);

        $settings->fill([
            'is_enabled' => $data['isEnabled'],
            'dry_run' => $data['dryRun'],
            'disconnect_window_start' => $data['disconnectWindowStart'],
            'disconnect_window_end' => $data['disconnectWindowEnd'],
            'max_suspend_percent' => $data['maxSuspendPercent'],
            'timezone' => $data['timezone'],
        ])->save();

        return response()->json(['data' => $this->present($settings)]);
    }

    public function logs(Request $request)
    {
        $logs = MikrotikLog::with(['mikrotik' => fn ($query) => $query->select('id', 'name')])
            ->where('site_id', auth()->user()->site_id)
            ->when($request->get('result'), fn ($query, $result) => $query->where('result', $result))
            ->latest('id')
            ->paginate(min((int) $request->get('per_page', 20), 100));

        return response()->json([
            'data' => $logs->getCollection()->map(fn (MikrotikLog $log) => [
                'id' => $log->id,
                'router' => $log->mikrotik?->name,
                'username' => $log->pppoe_username,
                'action' => $log->action,
                'result' => $log->result,
                'trigger' => $log->trigger,
                'message' => $log->message,
                'createdAt' => $log->created_at,
            ]),
            'meta' => ['total' => $logs->total(), 'currentPage' => $logs->currentPage(), 'lastPage' => $logs->lastPage()],
        ]);
    }

    private function present(MikrotikSetting $settings): array
    {
        return [
            'isEnabled' => $settings->is_enabled,
            'dryRun' => $settings->dry_run,
            'disconnectWindowStart' => $settings->disconnect_window_start,
            'disconnectWindowEnd' => $settings->disconnect_window_end,
            'maxSuspendPercent' => $settings->max_suspend_percent,
            'timezone' => $settings->timezone,
        ];
    }
}
