<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\ProductsModel;
use App\Services\ProductsService;
use App\Services\SystemLogService;
use App\Traits\Language;
use Validator;
use Illuminate\Support\Facades\Input;
use View;
use Request;
Use Illuminate\Support\Facades\Redirect;
use Config;

class ProductsController extends Controller
{
    use Language;

    private $systemLogs;
    private $language;
    private $productsModel;
    private $productsService;

    public function __construct()
    {
        $this->systemLogs = new SystemLogService();
        $this->productsModel = new ProductsModel();
        $this->productsService = new ProductsService();
    }

    /**
     * @return array
     */
    private function getDataAndPagination()
    {
        $dataWithProducts = [
            'products' => $this->productsService->getProducts(),
            'productsPaginate' => $this->productsService->getPagination()
        ];

        return $dataWithProducts;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return View::make('crm.products.index')->with($this->getDataAndPagination());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return View::make('crm.products.create')->with([
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

        $validator = Validator::make($allInputs, $this->productsModel->getRules('STORE'));

        if ($validator->fails()) {
            return Redirect::to('products/create')->with('message_danger', $validator->errors());
        } else {
            if ($product = $this->productsService->execute($allInputs)) {
                $this->systemLogs->insertSystemLogs('Product has been add with id: '. $product, $this->systemLogs::successCode);
                return Redirect::to('products')->with('message_success', $this->getMessage('messages.SuccessProductsStore'));
            } else {
                return Redirect::back()->with('message_success', $this->getMessage('messages.ErrorProductsStore'));
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
        return View::make('crm.products.show')
            ->with([
                'products' => $this->productsService->getProduct($id),
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        return View::make('crm.products.edit')
            ->with('products', $this->productsService->getProduct($id));
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

        $validator = Validator::make($allInputs, $this->productsModel->getRules('STORE'));

        if ($validator->fails()) {
            return Redirect::back()->with('message_danger', $validator);
        } else {
            if ($this->productsService->update($id, $allInputs)) {
                return Redirect::to('products')->with('message_success', $this->getMessage('messages.SuccessProductsStore'));
            } else {
                return Redirect::back()->with('message_danger', $this->getMessage('messages.ErrorProductsStore'));
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
        $productsDetails = $this->productsService->getProduct($id);
        $productsDetails->delete();

        $this->systemLogs->insertSystemLogs('ProductsModel has been deleted with id: ' . $productsDetails->id, $this->systemLogs::successCode);


        return Redirect::to('products')->with('message_success', $this->getMessage('messages.SuccessProductsDelete'));
    }

    /**
     * @param $id
     * @param $value
     * @return mixed
     */
    public function isActiveFunction($id, $value)
    {
        if ($this->productsService->loadIsActiveFunction($id, $value)) {
            $this->systemLogs->insertSystemLogs('ProductsModel has been enabled with id: ' . $id, $this->systemLogs::successCode);
            return Redirect::back()->with('message_success', $this->getMessage('messages.SuccessProductsActive'));
        } else {
            return Redirect::back()->with('message_danger', $this->getMessage('messages.ProductsIsActived'));
        }
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function search()
    {
        $getValueInput = Request::input('search');
        $findProductsByValue = $this->productsService->loadSearch($getValueInput);
        $dataOfProducts = $this->getDataAndPagination();

        if (!$findProductsByValue > 0) {
            return redirect('products')->with('message_danger', $this->getMessage('messages.ThereIsNoProducts'));
        } else {
            $dataOfProducts += ['products_search' => $findProductsByValue];
            Redirect::to('products/search')->with('message_success', 'Find ' . $findProductsByValue . ' products!');
        }

        return View::make('crm.products.index')->with($dataOfProducts);
    }
}
