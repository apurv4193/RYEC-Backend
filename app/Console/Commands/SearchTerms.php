<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\SearchTerm;
use App\TempSearchTerm;
use App\Category;
use App\Metatag;
use Helpers;
use Config;
use DB;

class SearchTerms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'searchTerms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search Terms';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $objTempSearchTerm = new TempSearchTerm();
        $tempSearchTerms = $objTempSearchTerm->get();
        if(count($tempSearchTerms) > 0)
        {
            foreach ($tempSearchTerms as $term) 
            {
                $objSearchTerm = new SearchTerm();
                $searchDetail = $objSearchTerm->where('search_term',$term->search_term)->where('city',$term->city)->first();
                if(count($searchDetail) > 0)
                {
                    $searchDetail->count += 1;
                    $searchDetail->save();
                }
                else
                {
                    $tearmArray = [];

                    $tearmArray['search_term'] = $term->search_term;
                    $tearmArray['city'] = $term->city;
                    $tearmArray['count'] = 1;

                    $objCategory = new Category();
                    $categoryDetail = $objCategory->where('name',$term->search_term)->first();
                    if(count($categoryDetail) > 0)
                    {
                        $tearmArray['type'] = 1;
                    }
                    else
                    {
                        $objMetatag = new Metatag();
                        $metaTagDetail = $objMetatag->where('tag',$term->search_term)->first();
                        if(count($metaTagDetail) > 0)
                        {
                            $tearmArray['type'] = 2;
                        }
                        else
                        {
                            $tearmArray['type'] = 3;
                        }
                    }

                    $objSearchTerm->create($tearmArray);
                }
            }
            $objTempSearchTerm->truncate();
        }
    }
}
