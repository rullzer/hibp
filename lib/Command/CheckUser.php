<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\HIBP\Command;

use OCA\HIBP\Service\HIBPService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUser extends Command {
	
    private HIBPService $HIBPService;

	public function __construct(HIBPService $HIBPService) {
		parent::__construct();

        $this->HIBPService = $HIBPService;
	}

	protected function configure() {
		$this
			->setName('hibp:check-user')
			->setDescription('Check a user against HIBP.');

        $this->addArgument('user-id', InputArgument::REQUIRED, 'The userid to check');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
        $this->HIBPService->check($input->getArgument('user-id'));
        return 0;
	}
}
