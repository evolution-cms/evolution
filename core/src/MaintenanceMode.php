<?php namespace EvolutionCMS;

use EvolutionCMS\Models\SystemSetting;
use Illuminate\Contracts\Foundation\MaintenanceMode as MaintenanceModeInterface;

class MaintenanceMode implements MaintenanceModeInterface
{
    /**
     * Take the application down for maintenance.
     * Sets site_status to 0 (offline). Optionally stores a message via the
     * 'message' key in $payload into site_unavailable_message.
     *
     * @param  array  $payload
     * @return void
     */
    public function activate(array $payload): void
    {
        SystemSetting::query()->updateOrCreate(
            ['setting_name' => 'site_status'],
            ['setting_value' => '0']
        );

        evo()->setConfig('site_status', false);

        if (isset($payload['message'])) {
            SystemSetting::query()->updateOrCreate(
                ['setting_name' => 'site_unavailable_message'],
                ['setting_value' => $payload['message']]
            );

            evo()->setConfig('site_unavailable_message', $payload['message']);
        }
    }

    /**
     * Take the application out of maintenance.
     * Sets site_status to 1 (online).
     *
     * @return void
     */
    public function deactivate(): void
    {
        SystemSetting::query()->updateOrCreate(
            ['setting_name' => 'site_status'],
            ['setting_value' => '1']
        );

        evo()->setConfig('site_status', true);
    }

    /**
     * Determine if the application is currently down for maintenance.
     * site_status = true means online; false means offline/maintenance.
     *
     * @return bool
     */
    public function active(): bool
    {
        return !evo()->getConfig('site_status');
    }

    /**
     * Get the data array which was provided when the application was placed
     * into maintenance. Maps EVO's offline settings to a standard payload.
     *
     * @return array
     */
    public function data(): array
    {
        return [
            'message' => evo()->getConfig('site_unavailable_message', ''),
        ];
    }
}
