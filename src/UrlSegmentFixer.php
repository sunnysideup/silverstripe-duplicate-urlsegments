<?php

namespace Sunnysideup\DuplicateURLSegments;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class UrlSegmentFixer extends BuildTask
{
    protected string $title = 'Remove -2, -3, -4, -5, etc... from URLSegment';

    protected static string $description = 'Removes unnecessary appendixes from Page URLSegments';

    protected static string $commandName = 'urlsegmentfixer';

    protected $enabled = true;

    protected $forReal = false;

    protected $max = 9;

    public function getOptions(): array
    {
        return [
            new InputOption('go', 'g', InputOption::VALUE_NONE, 'Run for real (not just a test run)'),
            new InputOption('max', 'm', InputOption::VALUE_REQUIRED, 'Maximum suffix number to check', 9),
        ];
    }

    public function setForReal(bool $b)
    {
        $this->forReal = $b;
        return $this;
    }

    public function setMax(int $max)
    {
        $this->max = $max;
        return $this;
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        if ($input->getOption('go')) {
            $this->forReal = true;
        }

        $maxOption = $input->getOption('max');
        if ($maxOption !== null) {
            $this->max = (int) $maxOption;
        }

        if ($this->forReal) {
            $output->writeln('<h4>Running for real!</h4>');
        } else {
            $output->writeln('<h4>Test Only - add --go flag to run for real</h4>');
        }

        $i = 1;
        while ($i < $this->max) {
            ++$i;
            $appendix = '-' . $i;
            $list = SiteTree::get()->filter(['URLSegment:EndsWith' => $appendix]);
            foreach ($list as $page) {
                $this->fixOnePageForExecute($page, $output);
            }
        }

        return Command::SUCCESS;
    }

    protected function fixOnePageForExecute($page, PolyOutput $output)
    {
        $old = $page->URLSegment;
        $cleanUrlSegment = $page->generateURLSegment($page->Title);
        if ($cleanUrlSegment !== $old) {
            $output->writeForHtml($this->pageObjectToLink($page));

            if ($this->forReal) {
                $page->URLSegment = $cleanUrlSegment;
                $isPublished = $page->isPublished();
                $page->writeToStage(Versioned::DRAFT);
                if ($isPublished) {
                    $page->publishSingle();
                }

                $page = SiteTree::get()->byID($page->ID);
                if ($page->URLSegment === $cleanUrlSegment) {
                    $output->writeln('... FIXED! from ' . $old . ' to ' . $cleanUrlSegment);
                } else {
                    $output->writeln('... COULD NOT FIX from ' . $old . ' to ' . $cleanUrlSegment);
                }
            }
        }
    }

    public function fixOnePage($page)
    {
        $old = $page->URLSegment;
        $cleanUrlSegment = $page->generateURLSegment($page->Title);
        if ($cleanUrlSegment !== $old) {
            DB::alteration_message($this->pageObjectToLink($page));

            if ($this->forReal) {
                $page->URLSegment = $cleanUrlSegment;
                $isPublished = $page->isPublished();
                $page->writeToStage(Versioned::DRAFT);
                if ($isPublished) {
                    $page->publishSingle();
                }

                $page = SiteTree::get()->byID($page->ID);
                if ($page->URLSegment === $cleanUrlSegment) {
                    DB::alteration_message('... FIXED! from ' . $old . ' to ' . $cleanUrlSegment, 'created');
                } else {
                    DB::alteration_message('... COULD NOT FIX from ' . $old . ' to ' . $cleanUrlSegment, 'deleted');
                }
            }
        }
    }

    protected function pageObjectToLink($page): string
    {
        // @TODO (SS6 upgrade): Director::is_cli() removed - assuming CLI context in execute()
        $v = $page->Link() . ': ' . $page->Title;

        return str_replace('?stage=Stage', '', $v);
    }
}
