<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use \Illuminate\Http\Response;
use \Symfony\Component\DomCrawler\Crawler;

class TaskTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    public function setUp(): void {
        parent::setUp();
        $this->init_db();
    }

    private function init_db() {
        \Artisan::call('migrate:refresh');
        \Artisan::call('db:seed');
    }

    private function dom ($html) {
        $dom = new Crawler();
        $dom->addHTMLContent($html, "UTF-8");
        return $dom;
    }

    private function check_HTML_have_n_taskrow_and_names($html, $taskrow_number, $name_array) {
        $tasks_dom = $this->dom($html);
        $taskrows_dom = $tasks_dom->filter(".task-row");
        $this->assertSame($taskrow_number, count($taskrows_dom));
        $taskrow_texts = $taskrows_dom->each(function(Crawler $node, $i){
            $taskrow_text = $node->filter("div.content-area")->eq(0)->text();
            return $taskrow_text;
        });
        $i = 0;
        foreach ( $taskrow_texts as $taskrow_text ) {
            $this->assertSame($name_array[$i], $taskrow_text);
            $i += 1;
        }
        $done_tasks = $taskrows_dom->each(function(Crawler $node, $i){
            $is_done_text = $node->filter("div.done-area")->eq(0)->text();
            return $is_done_text;
        });
        foreach ( $done_tasks as $done_task ) {
            $this->assertsame($done_task, "完了");
        }   
    }

    private function get_pageData_and_check_status($url) {
        $response = $this->get($url);
        $response->assertStatus(Response::HTTP_OK);
        $response_html = $response->getContent();
        return $response_html;
    }

    private function post_pageData_and_check_status($url, $params, $redirect_url = null) {
        $response = $this->post($url, $params);
        $response->assertStatus(302);
        if ($redirect_url != null) {
            $response->assertRedirect($redirect_url);
            $response = $this->get($redirect_url);
        }
        $response_html = $response->getContent();
        return $response_html;
    }

    // < Top Page >
    // 
    // test whether top page display seed 3 data and done button
    public function test_taskListPage_have_3seedtasks()
    {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $taskrow_names = ["test task1", "test task2", "test task3"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 3, $taskrow_names);
    }
    // test whether top page display insertion form
    public function test_taskListPage_have_insertion_form() {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $task_dom = $this->dom($response_html);
        $insertion_form = $task_dom->filter(".insertion-form");
        $this->assertSame($insertion_form->eq(0)->attr("action"), "/tasks");
        $this->assertSame($insertion_form->eq(0)->attr("method"), "POST");
        $insertion_input = $insertion_form->eq(0)->filter("input")->eq(1)->attr('placeholder');
        $this->assertSame($insertion_input, "洗濯物をする...");
        $insertion_button = $insertion_form->eq(0)->filter("button")->eq(0)->text();
        $this->assertSame($insertion_button, "追加する");
    }

    // < Insert Page >
    //
    // test whtehre insertion page insertion success for 10 words task
    //   -> also check form action before insertion & current url after insertion
    public function test_taskInsertion_success_for_10words() {
        $this->init_db();
        $response_html = $this->post_pageData_and_check_status("/tasks", ["task_name" => "test"], "http://localhost/tasks");
        $taskrow_names = ["test task1", "test task2", "test task3", "test"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 4, $taskrow_names);
    }
    // test whether insertion page insertion fail for blank task
    public function test_taskInsertion_fail_for_blankWrds() {
        $this->init_db();
        $response_html = $this->post_pageData_and_check_status("/tasks", ["task_name" => ""], "http://localhost/tasks");
        $taskrow_names = ["test task1", "test task2", "test task3"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 3, $taskrow_names);
    }
    // test whether insertion page insertion fail for more than 100 words task
    public function test_taskInsertion_fail_for_moreThan100Words() {
        $this->init_db();
        $response_html = $this->post_pageData_and_check_status("/tasks", ["task_name" => "test task test task test task test task test task test task test task test task test task test task 1"], "http://localhost/tasks");
        $taskrow_names = ["test task1", "test task2", "test task3"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 3, $taskrow_names);
    }
    // test whether insertion page insertion success for 100 words task
    public function test_taskInsertion_success_for_100words() {
        $this->init_db();
        $response_html = $this->post_pageData_and_check_status("/tasks", ["task_name" => "test task test task test task test task test task test task test task test task test task test taska"], "http://localhost/tasks");
        $taskrow_names = ["test task1", "test task2", "test task3", "test task test task test task test task test task test task test task test task test task test taska"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 4, $taskrow_names);
    }

    // < Edit Link from Top Page >
    //
    // test whether top page have edit page link and it is valid
    // test whether edit page have form action & input

    // < Edit Page >
    //
    // test whether edit page edit success for 10 words task
    // test whether edit page edit fail for blank task
    // test whether edit page edit fail for more than 100 words task
    // test whether edit page edit success for 100 word task

    // < Done Link from Top Page >
    // 
    // test whether top page have done link
    // test whether done form is valid
    //   -> check whether task is deleted after done form is invoked

    // < Deletion Page >
    // 
    // test whether top page have deletion form 
    // test whether delete page deletion success
}
