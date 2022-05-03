<?php

/* creation of the ExitController class to pass requests to the database */
namespace App\Controller;

use App\Model\ExitManager;
use Doctrine\Common\Collections\Expr\Value;
use App\Service\AddFormService;

class ExitController extends AbstractController
{
    /**
    * List exits
    */
    public function index(): string
    {
        $exitManager = new ExitManager();
        $isLogIn = AdminController::isLogIn();
        $isFilterActive = $this->isFilterActive();
        $listOfActiveFilters = [];
        if (!empty($this->retrieveFilters())) {
            $filter = $this->retrieveFilters();
            $listOfActiveFilters = $this->listOfActiveFilters($filter);
            $exits = $exitManager->exitsFiltered($filter);
            header('location: /exits');
        } elseif (!empty($this->sessionRetrieveFilters())) {
            $filter = $this->sessionRetrieveFilters();
            $listOfActiveFilters = $this->listOfActiveFilters($filter);
            $exits = $exitManager->exitsFiltered($filter);
        } else {
            $exits = $exitManager->selectAll('name');
            $filter = null;
        }
        return $this->twig->render(
            'Exit/index.html.twig',
            ['exits' => $exits,'islogin' => $isLogIn, 'filters' => $filter,
            'isFilterActive' => $isFilterActive, 'listOfActiveFilters' => $listOfActiveFilters]
        );
    }

    /**
    * Retrieve filters from user
    */
    public function retrieveFilters()
    {
       // retrieve data from user
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST)) {
                if (!empty($_POST['jumpTypes'])) {
                    $filterByJumpTypes = $_POST['jumpTypes'];
                    $_SESSION['filterByJumpTypes'] = $filterByJumpTypes;
                } else {
                    $filterByJumpTypes = [];
                };
                if (!empty($_POST['department'])) {
                    $filterByDepartment = $_POST['department'];
                    $_SESSION['filterByDepartment'] = $filterByDepartment;
                } else {
                    $filterByDepartment = [];
                };
                $filter = [$filterByDepartment, $filterByJumpTypes];
                return $filter;
            };
        };
    }
    public function sessionRetrieveFilters()
    {
       // retrieve data from user
        if (!empty($_SESSION['filterByJumpTypes']) || !empty($_SESSION['filterByDepartment'])) {
            if (!empty($_SESSION['filterByJumpTypes'])) {
                $filterByJumpTypes = $_SESSION['filterByJumpTypes'];
            } else {
                $filterByJumpTypes = [];
            };
            if (!empty($_SESSION['filterByDepartment'])) {
                $filterByDepartment = $_SESSION['filterByDepartment'];
            } else {
                $filterByDepartment = [];
            };
            $filter = [$filterByDepartment, $filterByJumpTypes];
            return $filter;
        };
    }

    /**
    * List the active filters as a string in order to be reminded to user
    */
    public function listOfActiveFilters($filter)
    {
        if ($this->isFilterActive() == true) {
            $filterByDepartment = $filter[0];
            $filterByJumpTypes = $filter[1];
            if (count($filterByDepartment) == 0 && count($filterByJumpTypes) == 0) {
                $listOfActiveFilters = [];
            } elseif (count($filterByDepartment) == 0) {
                $filterByJumpTypes = $this->convertTypeJumpValueInId($filterByJumpTypes);
                $listOfActiveFilters = implode("/ ", $filterByJumpTypes);
            } elseif (count($filterByJumpTypes) == 0) {
                $listOfActiveFilters = implode("/ ", $filterByDepartment);
            } else {
                $filterByJumpTypes = $this->convertTypeJumpValueInId($filterByJumpTypes);
                $listOfActiveFilters = implode("/ ", $filterByDepartment) . "/" . implode("/ ", $filterByJumpTypes);
            };
            return $listOfActiveFilters;
        };
    }

    public function convertTypeJumpValueInId($filterByJumpTypes): array|string
    {
        $convertTable = ["Static-line", "Sans Glisseur", "Lisse", "Track Pantz", "Track Pantz Monopièce", "Wingsuit"];
        $convertedFilter = [];
        foreach ($filterByJumpTypes as $filterByJumpType) {
            $convertedFilter [] = $convertTable[$filterByJumpType - 1];
        }
        return $convertedFilter;
    }

    /**
    * Indicates if a filter have been done
    */
    public function isFilterActive(): bool
    {
        if (!empty($this->retrieveFilters()) || $this->sessionRetrieveFilters()) {
            return true;
        } else {
            return false;
        };
    }

    /**
     * Show informations for a specific exit
     */
    public function show(int $id): string
    {
        $exitManager = new ExitManager();
        $exit = $exitManager->selectOneById($id);
        $isLogIn = AdminController::isLogIn();
        $typeJumpByExit = $exitManager->selectTypeJumpByExitId($id);
        return $this->twig->render('Exit/show.html.twig', ['exit' => $exit,
                                                            'typeJumpByExit' => $typeJumpByExit,
                                                            'islogin' => $isLogIn]);
    }

    /**
     * Edit a specific exit
     */
    public function edit(int $id): ?string
    {
        $exitManager = new ExitManager();
        $exit = $exitManager->selectOneById($id);
        $isLogIn = AdminController::isLogIn();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // clean $_POST data
            $exit = array_map('trim', $_POST);

            // TODO validations (length, format...)

            // if validation is ok, update and redirection
            $exitManager->update($exit);

            header('Location: /exits/show?id=' . $id);

            // we are redirecting so we don't want any content rendered
            return null;
        }

        return $this->twig->render('Exit/edit.html.twig', [
            'exit' => $exit,
            'islogin' => $isLogIn]);
    }
    /**
     * Delete a specific exit
     */
    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = trim($_POST['id']);
            $exitManager = new ExitManager();
            $exitManager->delete((int)$id);

            header('Location:/exits');
        }
    }

    /**
     * Créer nouvel exit
     */
    public function add(): ?string
    {
        $isLogIn = AdminController::isLogIn();
        $errorMessages = [];
        $accessmessage = AdminController::accessDenied();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $uploadDir = 'assets/images/'; // definir le dossier de stockage de l'image
            $exit = AddFormService::trimPostData(); // suppression des espace
            $uploadFile = $uploadDir . basename($_FILES['image']['name']);
            $errorMessages = AddFormService::isEmpty($exit, $errorMessages);
            $errorMessages = AddFormService::checkLengthData($exit, $errorMessages);
            if (!empty($_FILES['image']['name'])) {
                $explodeName = explode('.', basename($_FILES['image']['name']));
                $name = $explodeName[0];
                $extension = strToLower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $uniqName = $name . uniqid('', true) . "." . $extension;
                $uploadFile = $uploadDir . $uniqName;
                $errorMessages = AddFormService::validateExtension($errorMessages);
                $errorMessages = AddFormService::validateMaxFileSize($errorMessages);
            } if (empty($errorMessages)) {
                move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile);
                $exit ['image'] = '/' . $uploadFile;
                $exitManager = new ExitManager();
                $id = $exitManager->insert($exit);
                if (!empty($exit['jumpTypes'])) {
                    $exit['value'] = $exit['jumpTypes'];
                    $exitManager->insertJumpType($id, $exit['value']);
                }
                header('Location:/exits/show?id=' . $id);
                return null;
            }
        }
        return $this->twig->render('Exit/add.html.twig', ['error_messages' => $errorMessages,
                                                            'islogin' => $isLogIn,
                                                            'accessdenied' => $accessmessage]);
    }
}
