<?php

namespace BusinessLogic\Categories;


use BusinessLogic\Exceptions\AccessViolationException;
use BusinessLogic\Exceptions\ValidationException;
use BusinessLogic\Security\PermissionChecker;
use BusinessLogic\Security\UserPrivilege;
use BusinessLogic\ValidationModel;
use DataAccess\Categories\CategoryGateway;

class CategoryHandler {
    /* @var $categoryGateway CategoryGateway */
    private $categoryGateway;

    /* @var $permissionChecker PermissionChecker */
    private $permissionChecker;

    function __construct($categoryGateway, $permissionChecker) {
        $this->categoryGateway = $categoryGateway;
        $this->permissionChecker = $permissionChecker;
    }

    /**
     * @param $category Category
     * @param $heskSettings array
     * @return Category The newly created category with ID
     * @throws ValidationException When validation fails
     */
    //TODO Test
    function createCategory($category, $userContext, $heskSettings) {
        $validationModel = $this->validate($category, $userContext);

        if (count($validationModel->errorKeys) > 0) {
            throw new ValidationException($validationModel);
        }

        $id = $this->categoryGateway->createCategory($category, $heskSettings);

        $allCategories = $this->categoryGateway->getAllCategories($heskSettings);

        return $allCategories[$id];
    }

    /**
     * @param $category Category
     * @param $userContext
     * @param $creating bool
     * @return ValidationModel
     * @throws AccessViolationException
     */
    //TODO Test
    private function validate($category, $userContext, $creating = true) {
        $validationModel = new ValidationModel();

        if (!$this->permissionChecker->doesUserHavePermission($userContext, UserPrivilege::CAN_MANAGE_CATEGORIES)) {
            throw new AccessViolationException('User cannot manage categories!');
        }

        if (!$creating && $category->id < 1) {
            $validationModel->errorKeys[] = 'ID_MISSING';
        }

        if ($category->backgroundColor === null || trim($category->backgroundColor) === '') {
            $validationModel->errorKeys[] = 'BACKGROUND_COLOR_MISSING';
        }

        if ($category->foregroundColor === null || trim($category->foregroundColor) === '') {
            $validationModel->errorKeys[] = 'FOREGROUND_COLOR_MISSING';
        }

        if ($category->name === null || trim($category->name) === '') {
            $validationModel->errorKeys[] = 'NAME_MISSING';
        }

        if ($category->priority === null || intval($category->priority) < 0 || intval($category->priority) > 3) {
            $validationModel->errorKeys[] = 'INVALID_PRIORITY';
        }

        if ($category->autoAssign === null || !is_bool($category->autoAssign)) {
            $validationModel->errorKeys[] = 'INVALID_AUTOASSIGN';
        }

        if ($category->displayBorder === null || !is_bool($category->displayBorder)) {
            $validationModel->errorKeys[] = 'INVALID_DISPLAY_BORDER';
        }

        if ($category->type === null || (intval($category->type) !== 0 && intval($category->type) !== 1)) {
            $validationModel->errorKeys[] = 'INVALID_TYPE';
        }

        if ($category->type === null || intval($category->type) < 0 || intval($category->type) > 2) {
            $validationModel->errorKeys[] = 'INVALID_TYPE';
        }

        return $validationModel;
    }

    /**
     * @param $category Category
     * @param $heskSettings array
     * @return Category
     * @throws ValidationException
     */
    function editCategory($category, $userContext, $heskSettings) {
        $validationModel = $this->validate($category, $userContext, false);

        if (count($validationModel->errorKeys) > 0) {
            throw new ValidationException($validationModel);
        }

        $this->categoryGateway->updateCategory($category, $heskSettings);
        $this->categoryGateway->resortAllCategories($heskSettings);

        $allCategories = $this->categoryGateway->getAllCategories($heskSettings);

        return $allCategories[$category->id];
    }

    function deleteCategory($id, $userContext, $heskSettings) {
        if (!$this->permissionChecker->doesUserHavePermission($userContext, UserPrivilege::CAN_MANAGE_CATEGORIES)) {
            throw new AccessViolationException('User cannot manage categories!');
        }

        $this->categoryGateway->deleteCategory($id, $heskSettings);
        $this->categoryGateway->resortAllCategories($heskSettings);
    }
}