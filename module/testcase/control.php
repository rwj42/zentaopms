<?php
/**
 * The control file of case currentModule of ZenTaoMS.
 *
 * ZenTaoMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * ZenTaoMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with ZenTaoMS.  If not, see <http://www.gnu.org/licenses/>.  
 *
 * @copyright   Copyright: 2009 Chunsheng Wang
 * @author      Chunsheng Wang <wwccss@263.net>
 * @package     case
 * @version     $Id$
 * @link        http://www.zentao.cn
 */
class testcase extends control
{
    private $products = array();

    /* 构造函数，加载story, release, tree等模块。*/
    public function __construct()
    {
        parent::__construct();
        $this->loadModel('product');
        $this->loadModel('tree');
        $this->loadModel('user');
        $this->products = $this->product->getPairs();
        $this->assign('products', $this->products);
    }

    /* case首页。*/
    public function index()
    {
        $this->locate($this->createLink('testcase', 'browse'));
    }

    /* 浏览一个产品下面的case。*/
    public function browse($productID = 0, $type = 'byModule', $param = 0, $orderBy = 'id|desc', $recTotal = 0, $recPerPage = 20, $pageID = 1)
    {
        if(empty($this->products)) $this->locate($this->createLink('product', 'create'));

        $productID = (int)$productID;
        if($productID == 0) $productID = key($this->products);

        /* 设置菜单。*/
        $this->testcase->setMenu($this->products, $productID);

        /* 设置模块ID。*/
        $type     = strtolower($type);
        $moduleID = ($type == 'bymodule') ? (int)$param : 0;

        /* 如果是按照模块查找，或者列出所有。*/
        if($type == 'bymodule' or $type == 'all')
        {
            $this->app->loadClass('pager', $static = true);
            $pager = pager::init($recTotal, $recPerPage, $pageID);
            $childModuleIds = $this->tree->getAllChildId($moduleID);
            $cases = $this->testcase->getModuleCases($productID, $childModuleIds, $orderBy, $pager);
        }
        
        $header['title'] = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->common;
        $position[]      = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $position[]      = $this->lang->testcase->common;

        $this->assign('header',        $header);
        $this->assign('position',      $position);
        $this->assign('productID',     $productID);
        $this->assign('productName',   $this->products[$productID]);
        $this->assign('moduleTree',    $this->tree->getTreeMenu($productID, $viewType = 'case', $rooteModuleID = 0, array('treeModel', 'createCaseLink')));
        $this->assign('type',          $type);
        $this->assign('cases',         $cases);
        $this->assign('pager',         $pager->get());
        $this->assign('users',         $this->user->getPairs($this->app->company->id, 'noletter'));
        $this->assign('moduleID',      $moduleID);
        $this->assign('param',         $param);
        $this->assign('recTotal',      $pager->recTotal);
        $this->assign('recPerPage',    $pager->recPerPage);
        $this->assign('orderBy',       $orderBy);

        $this->display();
    }

    /* 创建case。*/
    public function create($productID, $moduleID = 0)
    {
        $this->loadModel('story');
        if(!empty($_POST))
        {
            $caseID = $this->testcase->create();
            if(dao::isError()) die(js::error(dao::getError()));
            $this->loadModel('action');
            $this->action->create('case', $caseID, 'Opened');
            die(js::locate($this->createLink('testcase', 'browse', "productID=$_POST[product]&type=byModule&param=$_POST[module]"), 'parent'));
        }
        if(empty($this->products)) $this->locate($this->createLink('product', 'create'));

        $productID       = common::saveProductState($productID, key($this->products));
        $currentModuleID = (int)$moduleID;

        /* 设置菜单。*/
        $this->testcase->setMenu($this->products, $productID);

        $header['title'] = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->create;
        $position[]      = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $position[]      = $this->lang->testcase->create;

        $users = $this->user->getPairs($this->app->company->id);
        $this->assign('header',        $header);
        $this->assign('position',      $position);
        $this->assign('productID',     $productID);
        $this->assign('users',         $users);           
        $this->assign('productName',   $this->products[$productID]);
        $this->assign('moduleOptionMenu',  $this->tree->getOptionMenu($productID, $viewType = 'case', $rooteModuleID = 0));
        $this->assign('currentModuleID',   $currentModuleID);
        $this->assign('stories',       $this->story->getProductStoryPairs($productID));

        $this->display();
    }

    /* 查看一个case。*/
    public function view($caseID)
    {
        $this->loadModel('action');
        $case = $this->testcase->getById($caseID);
        $productID = $case->product;
        $header['title'] = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->view;
        $position[]      = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $position[]      = $this->lang->testcase->view;

        /* 设置菜单。*/
        $this->testcase->setMenu($this->products, $productID);

        $users   = $this->user->getPairs($this->app->company->id, 'noletter');
        $actions = $this->action->getList('case', $caseID);

        $this->assign('header',   $header);
        $this->assign('position', $position);
        $this->assign('case',     $case);
        $this->assign('actions',  $actions);
        $this->assign('productName', $this->products[$productID]);
        $this->assign('modulePath',  $this->tree->getParents($case->module));

        $this->display();
    }

    /* 编辑一个Bug。*/
    public function edit($caseID)
    {
        $this->loadModel('story');

        /* 更新case信息。*/
        if(!empty($_POST))
        {
            $changes = $this->testcase->update($caseID);
            if(dao::isError()) die(js::error(dao::getError()));
            if($this->post->comment != '' or !empty($changes))
            {
                $this->loadModel('action');
                $action = !empty($changes) ? 'Edited' : 'Commented';
                $actionID = $this->action->create('case', $caseID, $action, $this->post->comment);
                $this->action->logHistory($actionID, $changes);
            }
            die(js::locate($this->createLink('testcase', 'view', "caseID=$caseID"), 'parent'));
        }

        /* 生成表单。*/
        $case            = $this->testcase->getById($caseID);
        $productID       = $case->product;
        $currentModuleID = $case->module;
        $header['title'] = $this->products[$productID] . $this->lang->colon . $this->lang->testcase->edit;
        $position[]      = html::a($this->createLink('testcase', 'browse', "productID=$productID"), $this->products[$productID]);
        $position[]      = $this->lang->testcase->edit;

        /* 设置菜单。*/
        $this->testcase->setMenu($this->products, $productID);

        $users = $this->user->getPairs($this->app->company->id);
        $this->assign('header',        $header);
        $this->assign('position',      $position);
        $this->assign('productID',     $productID);
        $this->assign('productName',   $this->products[$productID]);
        $this->assign('moduleOptionMenu',  $this->tree->getOptionMenu($productID, $viewType = 'case', $rooteModuleID = 0));
        $this->assign('currentModuleID',   $currentModuleID);
        $this->assign('users',   $users);           
        $this->assign('stories', $this->story->getProductStoryPairs($productID));

        $this->assign('header',   $header);
        $this->assign('position', $position);
        $this->assign('case',      $case);

        $this->display();
    }

    public function delete($id)
    {
        $header['title'] = $this->lang->page->delete;
        $this->assign('header', $header);
        $this->display();
    }
}
