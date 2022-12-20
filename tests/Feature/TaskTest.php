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

    private function chcek_HTML_have_task_edit_button($html, $ids) {
        $tasks_dom = $this->dom($html);
        $taskrows_dom = $tasks_dom->filter(".task-row");
        $this->assertSame(count($taskrows_dom), count($ids));
        $taskrow_edits = $taskrows_dom->each(function(Crawler $node, $i) {
            $taskrow_edit_href = $node->filter("a")->eq(0)->attr("href");
            $taskrow_edit_name = $node->filter("a")->eq(0)->text();
            return array($taskrow_edit_href, $taskrow_edit_name);
        });
        $i = 0;
        $taskrow_edits_url = array();
        foreach($taskrow_edits as $taskrow_edit) {
            $expected_url = "/tasks/".$ids[$i]."/edit/";
            $this->assertSame($taskrow_edit[0], $expected_url);
            $this->assertSame($taskrow_edit[1], "編集");
            array_push($taskrow_edits_url , $taskrow_edit[0]);
            $i += 1;
        }
        return $taskrow_edits_url;
    }

    private function check_HTML_have_task_edit_form($html) {
        $tasks_dom = $this->dom($html);
        $edit_form = $tasks_dom->filter(".edit-task-form");
        $edit_form_input = $edit_form->filter("input")->eq(1)->attr("value");
        $this->assertNotSame($edit_form_input, null);
        $edit_form_button = $edit_form->filter("button")->eq(0)->text();
        $this->assertSame($edit_form_button, "編集する");
        $back_button = $edit_form->filter("a")->eq(0)->text();
        $this->assertSame($back_button, "戻る");
    }

    private function check_HTML_have_task_done_form($html, $ids) {
        $tasks_dom = $this->dom($html);
        $taskrows_dom = $tasks_dom->filter(".task-row");
        $taskrow_texts = $taskrows_dom->each(function(Crawler $node, $i){
            $taskrow_text = $node->filter("div.done-area")->eq(0)->text();
            $taskrow_action = $node->filter("div.done-area > form")->eq(0)->attr("action");
            $taskrow_method = $node->filter("div.done-area > form")->eq(0)->attr("method");
            return [$taskrow_text, $taskrow_action, $taskrow_method];
        });
        $i = 0;
        $task_dones_url = array();
        foreach($taskrow_texts as $taskrow_text) {
            $expected_url = "/tasks/".$ids[$i];
            $this->assertSame("完了", $taskrow_text[0]);
            $this->assertSame($expected_url, $taskrow_text[1]);
            $this->assertSame("POST", $taskrow_text[2]);
            array_push($task_dones_url, $taskrow_text[1]);
            $i += 1;
        }
        return $task_dones_url;
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

    private function put_pageData_and_check_status($url, $params, $redirect_url = null) {
        $response = $this->put($url, $params);
        $response->assertStatus(302);
        if ($redirect_url != null) {
            $response->assertRedirect($redirect_url);
            $response = $this->get($redirect_url);
        }
        $response_html = $response->getContent();
        return $response_html;
    }

    private function delete_pageData_and_check_status($url, $params, $redirect_url = null) {
        $response = $this->delete($url, $params);
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
    public function test_taskListPage_have_editPageLink() {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $ids = [1, 2, 3];
        $this->chcek_HTML_have_task_edit_button($response_html, $ids);
    }
    // test whether edit page have form action & input
    public function test_taskEditPage_have_editForm() {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $ids = [1, 2, 3];
        $edit_urls = $this->chcek_HTML_have_task_edit_button($response_html, $ids);
        foreach($edit_urls as $edit_url) {
            $edit_html = $this->get_pageData_and_check_status($edit_url);
            $this->check_HTML_have_task_edit_form($edit_html);
        }
    }
    // < Edit Page >
    //
    // test whether edit page edit success for 10 words task
    public function test_taskEditPage_edit_success_for_10Words() {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $ids = [1, 2, 3];
        $edit_urls = $this->chcek_HTML_have_task_edit_button($response_html, $ids);
        $edit_url = str_replace("/edit/", "", $edit_urls[0]);
        $data = array("task_name" => "testtest");
        $response_html = $this->put_pageData_and_check_status($edit_url, $data, "http://localhost/tasks");
        $taskrow_names = ["testtest", "test task2", "test task3"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 3, $taskrow_names);
    }
    // test whether edit page edit fail for blank task
    public function test_taskEditPage_edit_fail_for_blankWords() {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $ids = [1, 2, 3];
        $edit_urls = $this->chcek_HTML_have_task_edit_button($response_html, $ids);
        $edit_url = str_replace("/edit/", "", $edit_urls[0]);
        $data = array("task_name" => "");
        $response_html = $this->put_pageData_and_check_status($edit_url, $data, "http://localhost/tasks");
        $taskrow_names = ["test task1", "test task2", "test task3"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 3, $taskrow_names);
    }
    // test whether edit page edit fail for more than 100 words task
    public function test_taskEditPage_edit_fail_for_moreThan100Words() {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $ids = [1, 2, 3];
        $edit_urls = $this->chcek_HTML_have_task_edit_button($response_html, $ids);
        $edit_url = str_replace("/edit/", "", $edit_urls[0]);
        $data = array("task_name" => "test task test task test task test task test task test task test task test task test task test task 1");
        $response_html = $this->put_pageData_and_check_status($edit_url, $data, "http://localhost/tasks");
        $taskrow_names = ["test task1", "test task2", "test task3"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 3, $taskrow_names);
    }
    // test whether edit page edit success for 100 word task
    public function test_taskEditPage_edit_fail_for_100Words() {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $ids = [1, 2, 3];
        $edit_urls = $this->chcek_HTML_have_task_edit_button($response_html, $ids);
        $edit_url = str_replace("/edit/", "", $edit_urls[0]);
        $data = array("task_name" => "test task test task test task test task test task test task test task test task test task test taska");
        $response_html = $this->put_pageData_and_check_status($edit_url, $data, "http://localhost/tasks");
        $taskrow_names = ["test task test task test task test task test task test task test task test task test task test taska", "test task2", "test task3"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 3, $taskrow_names);    
    }

    // < Done Link from Top Page >
    // 
    // test whether top page have done link
    // test whether done form is valid
    //   -> check whether task is deleted after done form is invoked
    public function test_taskListPage_have_done_button() {
        $this->init_db();
        $response_html = $this->get_pageData_and_check_status("/tasks");
        $ids = [1, 2, 3];
        $done_urls = $this->check_HTML_have_task_done_form($response_html, $ids);
        $done_url = $done_urls[0];
        $data = array("status" => 0);
        $response_html = $this->put_pageData_and_check_status($done_url, $data, "http://localhost/tasks");
        $taskrow_names = ["test task2", "test task3"];
        $this->check_HTML_have_n_taskrow_and_names($response_html, 2, $taskrow_names);
    }

    // < Deletion Page >
    // 
    // test whether top page have deletion form 
    // test whether delete page deletion success
}
