<?php namespace Weblabz\Test;
/**
 * WebLabz LLC
 * User: lance
 * Date: 8/10/13
 * Time: 1:18 PM
 */
require_once __DIR__ .'/BaseTest.php';

use Weblabz\Scrapyd\Client;
class ClientTest extends BaseTest{

    private $client;
    private $scrapydHost='localhost';
    private $scrapydHostProtocol='http';
    private $scrapydHostPort=6800;
    private $project;
    public function setUp(){
        $this->client = new Client($this->scrapydHost, $this->scrapydHostProtocol, $this->scrapydHostPort);

        $this->project = array(
            'name'=>'testProject',
            'versions'=> array('test1', 'test2'),
            'egg'=> __DIR__.'/probate_spiders-1.1-py2.7.egg',
            'spiders' => array(
                'alameda'=>array(
                    'base_case_number'=>13687000,
                    'case_range'=>10,
                    '_job'=>uniqid()
                ),
                'lacounty'=>array(
                    'base_case_number'=>143290,
                    'case_range'=>10,
                    'setting'=>'CONCURRENT_REQUESTS=1',
                    '_job'=>uniqid()
                )
            ),
            'jobs'=> array()
        );
    }
    public function testBuildServiceUrl(){
        $service = 'listprojects.json';
        $url = $this->client->buildServiceUrl($service);
        $this->assertEquals($this->scrapydHostProtocol.'://'.$this->scrapydHost.':'.$this->scrapydHostPort.'/'.$service, $url);

    }
    public function testFlattenParams(){
        $params = array(
            'base_case_number'=>13687000,
            'case_range'=>10,
            'setting'=>array(
                'CONCURRENT_REQUESTS'=>1,
                'ANOTHER_SETTING'=>'booya'
            )
        );
        $result = $this->client->_flattenParams($params);
        $this->assertEquals("base_case_number=13687000&case_range=10&setting=CONCURRENT_REQUESTS=1&setting=ANOTHER_SETTING=booya", $result);
    }
    public function testSaveProject(){
        $project = $this->client->saveProject($this->project['name'], $this->project['versions'][0], $this->project['egg']);
        $this->assertEquals('ok', $project['status']);
    }
    public function testListProjects(){
        $projects = $this->client->getProjects();
        $this->assertEquals('ok', $projects['status']);
    }
    public function testListSpiders(){
        $spiders = $this->client->getSpiders($this->project['name']);
        $this->assertEquals('ok', $spiders['status']);
    }
    public function testListProjectVersions(){
        $versions = $this->client->getProjectVersions($this->project['name']);
        $this->assertEquals('ok', $versions['status']);
    }

    public function testAddProjectVersion(){
        $project = $this->client->saveProject($this->project['name'], $this->project['versions'][1], $this->project['egg']);
        $this->assertEquals('ok', $project['status']);

    }
    public function testScheduleJob(){
        foreach($this->project['spiders'] as $key=>$config){
            $job = $this->client->scheduleJob($this->project['name'], $key, $config);
            array_push($this->project['jobs'], $job['jobid']);
            $this->assertEquals('ok', $job['status']);
        }

    }
    public function testListProjectJobs(){
        $jobs = $this->client->getJobs($this->project['name']);
        $jobIDs = array();
        foreach($jobs['pending'] as $value){
            array_push($jobIDs, $value['id']);
        }
        foreach($jobs['running'] as $value){
            array_push($jobIDs, $value['id']);
        }
        foreach($jobs['finished'] as $value){
            array_push($jobIDs, $value['id']);
        }
        $this->assertEquals('ok', $jobs['status']);
        foreach($this->project['jobs'] as $jobid){
            $this->assertContains($jobid, $jobIDs);
        }
    }
    public function testGetJobLog(){
        $log = $this->client->getJobLog($this->project['name'], 'alameda', $this->project['spiders']['alameda']['_job']);
        $this->assertEquals('ok', 'ok');
    }
    public function testCancelProjectJob(){
        $jobs = $this->client->getJobs($this->project['name']);
        $jobIDs = array();
        foreach($jobs['pending'] as $value){
            array_push($jobIDs, $value['id']);
        }
        foreach($jobs['running'] as $value){
            array_push($jobIDs, $value['id']);
        }
        $response = $this->client->cancelJob($this->project['name'], $jobIDs[0]);
        $this->assertEquals('ok', $response['status']);
    }


    public function testDeleteProjectVersion(){
        $response = $this->client->deleteProjectVersion($this->project['name'], $this->project['versions'][1]);
        $this->assertEquals('ok', $response['status']);
    }
    public function testDeleteProject(){
        $response = $this->client->deleteProject($this->project['name']);
        $this->assertEquals('ok', $response['status']);
    }
}