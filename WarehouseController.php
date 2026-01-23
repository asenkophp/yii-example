<?php
declare(strict_types=1);

class WarehouseController extends Controller
{
    protected Warehouse $model;

    public function filters(): array
    {
        return [
            'accessControl',
            'postOnly + delete',
            'loadModel + view, update, delete',
        ];
    }

    public function filterLoadModel($filterChain): void
    {
        $id = Yii::app()->request->getQuery('id');
        if ($id === null) {
            throw new CHttpException(400, Yii::t('app', 'ID not specified.'));
        }

        $this->model = Warehouse::model()->findByPk((int)$id);
        if (!$this->model) {
            throw new CHttpException(404, Yii::t('app', 'Page not found.'));
        }

        $filterChain->run();
    }

    public function accessRules(): array
    {
        return [
            ['allow', 'actions' => ['index','view'], 'users' => ['*']],
            ['allow', 'actions' => ['create','update','delete'], 'roles' => ['admin','user']],
            ['deny', 'users' => ['*']],
        ];
    }

    public function actionIndex(): void
    {
        $dataProvider = new CActiveDataProvider('Warehouse');
        $this->render('index', compact('dataProvider'));
    }

    public function actionView(): void
    {
        $this->render('view', ['model' => $this->model]);
    }

    public function actionCreate(): void
    {
        $model = new Warehouse();
        if (Yii::app()->request->getPost('ajax') === 'warehouse-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        $postData = Yii::app()->request->getPost('Warehouse');
        if ($postData) {
            $model->attributes = $postData;
            if ($model->save()) {
                Yii::app()->user->setFlash('success', Yii::t('app', 'Warehouse created successfully.'));
                $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::app()->user->setFlash('error', CHtml::errorSummary($model));
            }
        }

        $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(): void
    {
        $model = $this->model;
        if (Yii::app()->request->getPost('ajax') === 'warehouse-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        $postData = Yii::app()->request->getPost('Warehouse');
        if ($postData) {
            $model->attributes = $postData;
            if ($model->save()) {
                Yii::app()->user->setFlash('success', Yii::t('app', 'Warehouse updated successfully.'));
                $this->redirect(['view', 'id' => $model->id]);
            } else {
                Yii::app()->user->setFlash('error', CHtml::errorSummary($model));
            }
        }

        $this->render('update', ['model' => $model]);
    }

    public function actionDelete(): void
    {
        $this->model->delete();
        Yii::app()->user->setFlash('success', Yii::t('app', 'Warehouse deleted successfully.'));

        if (!Yii::app()->request->getQuery('ajax')) {
            $this->redirect(['index']);
        }
    }
}
