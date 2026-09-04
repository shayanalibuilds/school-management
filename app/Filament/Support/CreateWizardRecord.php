<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\CreateRecord;

/**
 * Base page for resources whose form is a Wizard.
 *
 * The wizard itself renders "Create" and "Create another" inside its final
 * step (see the closing Actions component of each form), so those buttons
 * are only reachable once every step is complete. The page footer keeps
 * "Cancel" visible at every step.
 */
abstract class CreateWizardRecord extends CreateRecord
{
    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCancelFormAction(),
        ];
    }
}
