<?php

/**
 *  后台继承类
 * @file   Brand.php  
 * @date   2016-8-23 19:45:21 
 * @author Zhenxun Du<5552123@qq.com>  
 * @version    SVN:$Id:$ 
 */

namespace app\admin\controller;

use think\Loader;

class BrandController extends CommonController {

    public function index() {
        
        $where = array();
        if ($group_id = input('group_id')) {
            $where['t2.group_id'] = $group_id;
            
        }
        $lists = db('Brand')->alias('t1')->field('t1.*')
                ->where($where)
                ->join(config('database.prefix').'Brand_group_access t2', 't1.id=t2.uid', 'left')
                ->group('t1.id')
                ->order('t1.id desc')
                ->select();
     
        foreach ($lists as $k => $v) {
            //取出组名
            $lists[$k]['groups'] = '';
            $groups = Loader::model('Brand')->getUserGroups($v['id']);
            if ($groups) {
                $tmp = db('Brand_group')->field('name')->where('id', 'in', $groups)->select();

                foreach ($tmp as $vv) {
                    $lists[$k]['groups'] .= $vv['name'] . ',';
                }
                $lists[$k]['groups'] = trim($lists[$k]['groups'], ',');
            }
        }

        $this->assign('lists', $lists);
        return $this->fetch();
    }

    /*
     * 查看
     */

    public function info() {
        $id = input('id');
        if ($id) {
            //当前用户信息
            $info = model('Brand')->getInfo($id);
            $info['userGroups'] = Loader::model('Brand')->getUserGroups($id);
            $this->assign('info', $info);
        }

        //所有组信息
        $groups = model('BrandGroup')->getGroups();

        $this->assign('groups', $groups);
        return $this->fetch();
    }

    /*
     * 添加
     */

    public function add() {
        $data = input();
        $count = db('Brand')->where('username', $data['username'])->count();

        if ($count) {
            $this->error('用户名已存在');
        }

        if ($data['password'] != $data['rpassword']) {
            $this->error('两次密码不一致');
        }

        $data['password'] = md5($data['password']);

        $res = model('Brand')->allowField(true)->save($data);

        if ($res) {
            if (isset($data['groups'])) {
                $uid = model('Brand')->id;
                foreach ($data['groups'] as $v) {
                    db('Brand_group_access')->insert(['uid' => $uid, 'group_id' => $v]);
                }
            }
            $this->success('操作成功', url('index'));
        } else {
            $this->error('操作失败');
        }
    }

    /*
     * 修改
     */

    public function edit() {
        $data = input();
        db('Brand_group_access')->where(['uid' => $data['id']])->delete();

        if (isset($data['groups'])) {
            foreach ($data['groups'] as $v) {
                db('Brand_group_access')->insert(['uid' => $data['id'], 'group_id' => $v]);
            }
        }


        if (!$data['password']) {
            unset($data['password']);
        } else {
            if ($data['password'] != $data['rpassword']) {
                $this->error('两次密码不一致!');
            }
            $data['password'] = md5($data['password']);
        }

        $res = Loader::model('Brand')->editInfo(2, $data['id'], $data);

        if ($res) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /*
     * 删除
     */

    public function del() {
        $id = input('id');
        $res = db('Brand')->where(['id' => $id])->delete();
        if ($res) {
            db('Brand_group_access')->where(['uid' => $id])->delete();
            $this->success('操作成功', url('index'));
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 修改个人信息
     */
    public function public_edit_info() {
        $data = input();
        if (isset($data['dosubmit'])) {
            if (!$data['password']) {
                unset($data['password']);
            } else {
                if ($data['password'] != $data['rpassword']) {
                    $this->error('两次密码不一致!');
                }
                $data['password'] = md5($data['password']);
            }

            $res = Loader::model('Brand')->editInfo(2, $this->user_id, $data);

            if ($res) {
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        } else {
            $info = db('Brand')->where('id', $this->user_id)->find();
            $this->assign('info', $info);
            return $this->fetch();
        }
    }

}
