<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\EmployeesModel;
use App\Models\TasksModel;
use App\Services\SystemLogService;
use App\Services\TasksService;
use App\Traits\Language;
use Validator;
use Illuminate\Support\Facades\Input;
use View;
use Request;
Use Illuminate\Support\Facades\Redirect;
use Config;

class TasksController extends Controller
{
    use Language;

    private $systemLogs;
    private $taskModel;
    private $taskService;

    public function __construct()
    {
        $this->systemLogs = new SystemLogService();
        $this->taskModel = new TasksModel();
        $this->taskService = new TasksService();
    }

    /**
     * @return array
     */
    private function getDataAndPagination()
    {
        $dataOfTasks = [
            'tasks' => $this->taskService->getTasks(),
            'tasksPaginate' => $this->taskService->getPaginate()
        ];

        return $dataOfTasks;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return View::make('crm.tasks.index')->with($this->getDataAndPagination());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $dataOfEmployees = EmployeesModel::pluck('full_name', 'id');

        return View::make('crm.tasks.create')->with([
            'dataOfEmployees' => $dataOfEmployees,
            'inputText' => $this->getMessage('messages.InputText')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $allInputs = Input::all();

        $validator = Validator::make($allInputs, $this->taskModel->getRules('STORE'));

        if ($validator->fails()) {
            return Redirect::to('tasks/create')->with('message_danger', $validator->errors());
        } else {
            if ($task = $this->taskService->execute($allInputs)) {
                $this->systemLogs->insertSystemLogs('Task has been add with id: '. $task, $this->systemLogs::successCode);
                return Redirect::to('tasks')->with('message_success', $this->getMessage('messages.SuccessTasksStore'));
            } else {
                return Redirect::back()->with('message_danger', $this->getMessage('messages.ErrorTasksStore'));
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        return View::make('crm.tasks.show')
            ->with('tasks', $this->taskService->getTask($id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        $dataWithPluckOfEmployees = EmployeesModel::pluck('full_name', 'id');

        return View::make('crm.tasks.edit')
            ->with([
                'tasks' => $this->taskService->getTask($id),
                'employees' => $dataWithPluckOfEmployees
            ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $allInputs = Input::all();

        $validator = Validator::make($allInputs, $this->taskModel->getRules('STORE'));

        if ($validator->fails()) {
            return Redirect::back()->with('message_danger', $validator);
        } else {
            if ($this->taskService->update($id, $allInputs)) {
                return Redirect::to('tasks')->with('message_success', $this->getMessage('messages.SuccessTasksUpdate'));
            } else {
                return Redirect::back()->with('message_danger', $this->getMessage('messages.ErrorTasksUpdate'));
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     * @throws \Exception
     */
    public function destroy($id)
    {
        $dataOfTasks = $this->taskService->getTask($id);

        if($dataOfTasks->completed == 0) {
            return Redirect::back()->with('message_danger', $this->getMessage('messages.CantDeleteUnompletedTask'));
        } else {
            $dataOfTasks->delete();

            $this->systemLogs->insertSystemLogs('Tasks has been deleted with id: ' . $dataOfTasks->id, $this->systemLogs::successCode);

        }

        return Redirect::to('tasks')->with('message_success', $this->getMessage('messages.SuccessTasksDelete'));
    }

    /**
     * @param $id
     * @param $value
     * @return mixed
     */
    public function isActiveFunction($id, $value)
    {
        if ($this->taskService->loadIsActiveFunction($id, $value)) {
            $this->systemLogs->insertSystemLogs('Tasks has been enabled with id: ' . $id, $this->systemLogs::successCode);
            return Redirect::back()->with('message_success', $this->getMessage('messages.SuccessTasksActive'));
        } else {
            return Redirect::back()->with('message_danger', $this->getMessage('messages.ErrorTasksActive'));
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function search()
    {
        $getValueInput = Request::input('search');
        $findTasksByValue = $this->taskService->loadSearch($getValueInput);
        $dataOfTasks = $this->getDataAndPagination();

        if (!$findTasksByValue > 0) {
            return redirect('tasks')->with('message_danger', $this->getMessage('messages.ThereIsNoTasks'));
        } else {
            $dataOfTasks += ['tasks_search' => $findTasksByValue];
            Redirect::to('tasks/search')->with('message_success', 'Find ' . $findTasksByValue . ' deals!');
        }

        return View::make('crm.tasks.index')->with($dataOfTasks);
    }

    public function completedTask($id)
    {
        if ($this->taskService->loadIsCompletedFunction($id, TRUE)) {
            $this->systemLogs->insertSystemLogs('Tasks has been completed with id: ' . $id, $this->systemLogs::successCode);
            return Redirect::back()->with('message_success', $this->getMessage('messages.TasksCompleted'));
        } else {
            return Redirect::back()->with('message_danger', $this->getMessage('messages.TasksIsNotCompleted'));
        }
    }



    public function uncompletedTask($id)
    {
        if ($this->taskService->loadIsCompletedFunction($id, FALSE)) {
            $this->systemLogs->insertSystemLogs('Tasks has been uncompleted with id: ' . $id, $this->systemLogs::successCode);
            return Redirect::back()->with('message_success', $this->getMessage('messages.TasksunCompleted'));
        } else {
            return Redirect::back()->with('message_danger', $this->getMessage('messages.TasksIsNotunCompleted'));
        }
    }
}
