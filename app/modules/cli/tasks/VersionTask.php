<?php
declare(strict_types=1);

namespace AirLook\Modules\Cli\Tasks;

class VersionTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $config = $this->di->get('config');
        echo "AirLook " .$config['version'] ."\n";
        echo "PHP 7.2+\n";
        echo "Phalcon 4.0+";
    }

    public function devAction()
    {
        echo "PHP Version: 7.4.5\n";
        echo "Phalcon Version: 4.0.6\n";
        echo "MySQL Version: 5.7.23";
    }
}
