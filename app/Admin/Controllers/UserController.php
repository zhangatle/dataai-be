<?php

namespace App\Admin\Controllers;

use App\Models\Customer;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Hash;

class UserController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(['customer']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('password')->display(function ($value) {
                return $value;
            });
            $grid->column('customer', '企业名称')->display(function ($value) {
                return $value['name'];
            });
            $grid->column('is_active')->display(function ($value) {
                return $value == 1 ? '启用' : '禁用';
            });
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('customer.name', '企业名称');
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new User(['customer']), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('password');
            $show->field('remember_token');
            $show->field('customer.name');
            $show->field('is_active');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form(): Form
    {
        return Form::make(new User(), function (Form $form) {
            $form->display('id');
            $form->text('name')->required();
            $form->password("password")->required();
            $form->select('customer_id', '企业名称')->options(function () {
                $list = [];
                $customer = Customer::query()->where('is_active', 1)->select("id", "name")->get();
                foreach ($customer as $item) {
                    $list[$item->id] = $item->name;
                }
                return $list;
            })->required();
            $form->select("is_active")->options([1=>'启用',0=>'禁用']);
            $form->display('created_at');
            $form->display('updated_at');
            $form->saving(function (Form $form) {
                $form->password = Hash::make($form->password);
            });
        });
    }
}
