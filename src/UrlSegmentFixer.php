<?php

namespace Sunnysideup\DuplicateURLSegments;



use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;


use SilverStripe\CMS\Model\SiteTree;

use SilverStripe\Versioned\Versioned;

class UrlSegmentFixer extends BuildTask
{
    private static $segment = 'urlsegmentfixer';

    protected $title = 'Remove -2 from URLSegment';

    protected $description = 'Remove the -2 from the URLSegments on the website.';

    protected $enabled = true;

    protected $forReal = false;

    public function run($request)
    {
        if($request->getVar('go')) {
            $this->forReal = true;
        }
        if($this->forReal) {
            echo '<h4>Running for real!</h4>';
        } else {
            echo '<h4>Test Only - <a href="?go=1'.$this->Link().'">run for real</a></h4>';
        }
        $i = 0;
        while($i < 10) {
            $i++;
            $appendix = '-'.$i;
            $list = SiteTree::get()->filter(['URLSegment:PartialMatch' => $appendix]);
            foreach($list as $page) {
                $cleanUrlSegment = rtrim($page->URLSegment, $appendix);
                if($cleanUrlSegment !== $page->URLSegment) {
                    DB::alteration_message($this->pageObjectToLink($page).' ('.$page->URLSegment.') </strong> ');
                    $others = SiteTree::get()->filter(['URLSegment' => $cleanUrlSegment, 'ParentID' => $page->ParentID]);
                    $hasOthers = false;
                    foreach($others as $other) {
                        $hasOthers = true;
                        DB::alteration_message(
                            '... can not be changed because is an existing page with '.$cleanUrlSement.': '
                            . $this->pageObjectToLink($other)
                        );
                    }
                    if($hasOthers === false && $this->forReal) {
                        $page->URLSegment = $cleanUrlSegment;
                        $page->writeToStage(Versioned::DRAFT);
                        $page->publishRecursive();
                        DB::alteration_message('... FIXED! ');
                    }
                } else {
                    DB::alteration_message($this->pageObjectToLink($page) . ': could not decipher  => '.$page->URLSegment);
                }
                echo '<br /><br />';
            }
        }
    }

    protected function pageObjectToLink($page) : string
    {
        return '<a href="'.$page->CMSEditLink().'">✎</a> <a href="'.$page->Link().'">'.$page->Title.'</a>';
    }
}
