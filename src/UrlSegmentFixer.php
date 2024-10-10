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
    protected $title = 'Remove -2 from URLSegment';

    protected $description = 'Remove the -2 from the URLSegments on the website.';

    protected $enabled = true;

    protected $forReal = false;

    private static $segment = 'urlsegmentfixer';

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
        while ($i < 999) {
            ++$i;
            $appendix = '-' . $i;
            $list = SiteTree::get()->filter(['URLSegment:EndsWith' => $appendix]);
            foreach ($list as $page) {
                $cleanUrlSegment = $page->generateURLSegment($page->Title);
                if ($cleanUrlSegment !== $page->URLSegment) {
                    DB::alteration_message($this->pageObjectToLink($page));
                    $others = SiteTree::get()->filter(['URLSegment' => $cleanUrlSegment, 'ParentID' => $page->ParentID]);
                    $hasOthers = false;
                    foreach ($others as $other) {
                        $hasOthers = true;
                        DB::alteration_message('... can not be changed because there is an existing page: '. $this->pageObjectToLink($other));
                    }

                    if (false === $hasOthers && $this->forReal) {
                        $page->URLSegment = $cleanUrlSegment;
                        $page->writeToStage(Versioned::DRAFT);
                        $page->publishRecursive();
                        DB::alteration_message('... FIXED! ');
                    }
                }
            }
        }
    }

    protected function pageObjectToLink($page): string
    {
        if (Director::is_cli()) {
            return $page->Title.':  '.$this->Link();
        }
        return '<a href="' . $page->CMSEditLink() . '">✎</a> <a href="' . $page->Link() . '">' . $page->Title . '</a>';
    }
}
