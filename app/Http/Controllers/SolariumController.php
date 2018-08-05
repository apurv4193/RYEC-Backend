<?php

namespace App\Http\Controllers;

class SolariumController extends Controller
{
    protected $client;

    public function __construct(\Solarium\Client $client)
    {
        $this->client = $client;
    }

    public function ping()
    {
        // create a ping query
        $ping = $this->client->createPing();

        // execute the ping query
        try {
            $this->client->ping($ping);
            return response()->json('OK');
        } catch (\Solarium\Exception $e) {
            return response()->json('ERROR', 500);
        }
    }

    public function search()
    {
        $query = $this->client->createSelect();

        //$query->addFilterQuery(array('key'=>'demo', 'query'=>'banner_img_str:demo.png', 'tag'=>'include'));
        $resultset = $this->client->select($query);

        // display the total number of documents found by solr
        echo 'NumFound: ' . $resultset->getNumFound();

        // show documents using the resultset iterator
        foreach ($resultset as $document) {

            echo '<hr/><table>';

            // the documents are also iterable, to get all fields
            foreach ($document as $field => $value) {
                // this converts multivalue fields to a comma-separated string
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                echo '<tr><th>' . $field . '</th><td>' . $value . '</td></tr>';
            }

            echo '</table>';
        }
    }

    public function createUpdateDocument()
    {
        //dd($this->client);
        $update = $this->client->createUpdate();
        $doc = $update->createDocument();

        $doc->model = 'Category';
        $doc->model_id = 1;
        $doc->name = 'Real Estate';
        $doc->parent = '0';
        $doc->parent_name = '';
        $doc->category_slug = 'real-estate';
        $doc->category_logo = 'demo.png';
        $doc->banner_img = 'demo.png';
        $doc->metatags = ['demo', 'demo1'];
        $doc->custom_field = [
            [
                'id' => 1,
                'business_name' => 'demo'
            ],
            [
                'id' => 2,
                'business_name' => 'demo111'
            ]
        ];
        $doc->service_type = true;
        $doc->trending_service = true;
        $doc->trending_category = true;

        $update->addDocument($doc);
        $update->addCommit();
        $result = $this->client->update($update);
        echo '<pre>';print_r($result);exit;
    }

    public function deleteDocument($id = '')
    {
        // get an update query instance
        $update = $this->client->createUpdate();

        // add the delete query and a commit command to the update query
        if($id == 0) {
            $update->addDeleteQuery("*:*");
        } else {
            $update->addDeleteQuery("(model_id:$id)AND(model:Category)");
        }
        $update->addDeleteQuery("(model_id:$id)AND(model:Category)");
        $update->addCommit();

        // this executes the query and returns the result
        $result = $this->client->update($update);
        echo '<pre>';print_r($result);exit;
    }
}