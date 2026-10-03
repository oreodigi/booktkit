<?php
namespace Kreativdev\Installer;

use Illuminate\Support\ServiceProvider;

/** Retained dependency compatibility; supplier installation services are retired. */
class KdInstallerServiceProvider extends ServiceProvider
{
    public function boot() {}
    public function register() {}
}
