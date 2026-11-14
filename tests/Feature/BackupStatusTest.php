<?php

use App\Support\BackupStatus;

function backupDir(): string
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'respaldos_' . uniqid();
    mkdir($dir);

    return $dir;
}

function fakeBackup(string $dir, int $daysAgo): void
{
    $file = $dir . DIRECTORY_SEPARATOR . 'taller_' . uniqid() . '.zip';
    file_put_contents($file, 'zip');
    touch($file, now()->subDays($daysAgo)->timestamp);
}

describe('aviso de respaldos', function () {
    it('stays quiet when the backup folder is not configured', function () {
        expect((new BackupStatus(null, 3))->needsAttention())->toBeFalse();
    });

    it('warns when there is no backup or the latest one is too old', function () {
        $dir = backupDir();
        $status = new BackupStatus($dir, 3);
        expect($status->needsAttention())->toBeTrue()
            ->and($status->describe())->toContain('No se encontró ningún respaldo');

        fakeBackup($dir, 5);
        expect($status->needsAttention())->toBeTrue()
            ->and($status->describe())->toContain('hace 5 días');

        fakeBackup($dir, 0);
        expect($status->needsAttention())->toBeFalse();
    });

    it('shows the warning on the dashboard to administrators only', function () {
        $dir = backupDir();
        fakeBackup($dir, 10);
        config(['taller.respaldos_dir' => $dir]);

        [$admin, $branch] = branchUser(admin: true);
        [$employee] = branchUser(branch: $branch);

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->get(route('dashboard'))->assertOk()->assertSee('Los datos no se están respaldando');

        $this->actingAs($employee)->withSession(['current_branch_id' => $branch->id])
            ->get(route('dashboard'))->assertOk()->assertDontSee('Los datos no se están respaldando');

        fakeBackup($dir, 0);
        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->get(route('dashboard'))->assertOk()->assertDontSee('Los datos no se están respaldando');
    });
});
