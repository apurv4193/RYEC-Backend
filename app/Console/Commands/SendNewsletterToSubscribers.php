<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Newsletter;
use App\User;
use Helpers;
use Config;
use DB;

class SendNewsletterToSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendNewsletterToSubscribers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send newsletter to subscribers';

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
        $objNewsletter = new Newsletter();
        $objUser = new User();
        $sort = [];
        $sort['notify_subscribers'] = 1;
        $newsletters = $objNewsletter->getAll($sort);
        $subscribers = $objUser->getActiveUserSubscription();

        if($newsletters && $subscribers)
        {
            foreach($newsletters as $newsletter)
            {
                foreach($subscribers as $subscriber)
                {
                    $data['subscriber'] = $subscriber;
                    $data['newletter'] = $newsletter;
                    $newsletterBody = str_replace("/ckfinder/userfiles/", url('')."/ckfinder/userfiles/", $newsletter->body);
                    Helpers::sendMail('NewsLettersSubscription', $data, 'RYEC - Newsletter - ' . $newsletter->title, '', $subscriber->email, $newsletterBody);
                }
                $newsletter->notify_subscribers = 2;
                $newsletter->publish_status = 1;
                $newsletter->save();
            }
        }
    }
}
