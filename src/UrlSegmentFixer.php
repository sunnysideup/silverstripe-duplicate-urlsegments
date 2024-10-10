<?php

namespace Sunnysideup\DuplicateURLSegments;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\Parsers\URLSegmentFilter;

class UrlSegmentFixer extends BuildTask
{
    protected $title = 'Remove -2, -3, -4, -5, etc... from URLSegment';

    protected $description = 'Removes unnecessary appendixes from Page URLSegments';

    protected $enabled = true;

    protected $forReal = false;
    protected $max = 9;

    private static $segment = 'urlsegmentfixer';

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

    public function run($request)
    {
        if ($request->getVar('go')) {
            $this->forReal = true;
        }

        if ($this->forReal) {
            echo '<h4>Running for real!</h4>';
        } else {
            echo '<h4>Test Only - <a href="?go=1">run for real</a></h4>';
        }

        $i = 1;
        while ($i < $this->max) {
            ++$i;
            $appendix = '-' . $i;
            $list = SiteTree::get()->filter(['URLSegment:EndsWith' => $appendix]);
            foreach ($list as $page) {
                $this->fixOnePage($page);
            }
        }
    }

    public function fixOnePage($page)
    {
        $cleanUrlSegment = $page->generateURLSegment($page->Title);
        if ($cleanUrlSegment !== $page->URLSegment) {
            DB::alteration_message($this->pageObjectToLink($page));

            if ($this->forReal) {
                $page->URLSegment = $cleanUrlSegment;
                $isPublished = $page->isPublished();
                $page->writeToStage(Versioned::DRAFT);
                if ($isPublished) {
                    $page->publishSingle();
                }
                DB::alteration_message('... FIXED! ');
            }
        }
    }

    protected function pageObjectToLink($page): string
    {
        if (Director::is_cli()) {
            $v = $page->Link();
        } else {
            $v = '<a href="' . $page->CMSEditLink() . '">✎</a> <a href="' . $page->Link() . '">' . $page->Link() . ': ' . $page->Title . '</a>';
        }
        return str_replace('?stage=Stage', '', $v);
    }

}
