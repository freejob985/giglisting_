<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

class UtilsController extends Controller
{
    public function makeSymlinks(Request $request)
    {
        try {

            generate_symlink();

            echo "<p>The following symlinks generated successfully:</p>";
            echo "<ul>";
            echo "<li>./storage</li>";
            echo "<li>./laravel_project/public/storage</li>";
            echo "<li>./laravel_project/public/vendor</li>";
            echo "</ul>";
            die();

        }
        catch (Exception $e) {

            echo "<h3>Error:</h3>";
            echo $e->getMessage();
            echo "<h3>What to do:</h3>";
            echo "<ul>";
            echo "<li>Please make sure your web server running in administrator mode or has the permission to make/remove symlinks.</li>";
            echo "<li>Please manually delete the following folders (symlinks) and re-try go to " . route('utils.link') . "</li>";
            echo "<ul>";
            echo "<li>./storage</li>";
            echo "<li>./laravel_project/public/storage</li>";
            echo "<li>./laravel_project/public/vendor</li>";
            echo "</ul>";
            echo "</ul>";
            die();
        }
    }

    public function makeCache(Request $request)
    {
        try {

            clear_website_cache();
            generate_website_route_cache();
            generate_website_view_cache();

            echo "<p>Website cache generated successfully.</p>";
        }
        catch (Exception $e) {

            echo "<h3>Error:</h3>";
            echo $e->getMessage();
        }
    }
}
