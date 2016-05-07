<?php
/**
 * Created by PhpStorm.
 * User: darius0021
 * Date: 16.5.1
 * Time: 18.40
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Comments;
use AppBundle\Entity\Workout;
use AppBundle\Form\ActivateType;
use AppBundle\Form\CommentType;
use AppBundle\Form\WorkoutEditType;
use AppBundle\Form\WorkoutRatingType;
use AppBundle\Form\WorkoutType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\WorkoutHistory;

class WorkoutController extends Controller
{
    /**
     * Create a new Workout
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createWorkoutAction(Request $request)
    {
        $user = $this->getUser();
        if ($user==null) {
            return $this->redirectToRoute('fos_user_security_login');
        }
        $workoutService = $this->get('app.workoutservice');
        $workout = new Workout($user);
        $form = $this->createForm(WorkoutType::class, $workout);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $workoutService->saveWorkout($workout);
            return $this->redirect("../workouts/" . $workout->getId());
        }
        return $this->render('@App/Home/createWorkout.html.twig', array(
            'form' => $form->createView()
        ));
    }
    /**
     * Edit a Workout
     * @param int $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editWorkoutAction($id, Request $request)
    {
        $user = $this->getUser();
        $workout = $this->get('app.Repo')->getWorkout($id);
        $workoutService = $this->get('app.WorkoutService');
        if (!$workoutService->canEdit($user, $workout)) {
            //Numest i no permissions page.
            return new Response("NOPE!");
        }
        $form = $this->createForm(WorkoutType::class, $workout);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $workoutService->saveWorkout($workout);
            return $this->redirect("../workouts/" . $workout->getId());
        }
        return $this->render('@App/Home/createWorkout.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * Shows workout.
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showWorkoutAction($id, Request $request)
    {
        $workout = $this->get('app.repo')->getWorkout($id);
        if (!$workout) {
            //Numest i no permissions page.
            return new Response("NOPE!");
        }
        $workoutService = $this->get('app.workoutservice');
        $user = $this->getUser();
        $comment = new Comments($user, "");

        $commentForm = null;
        $activateForm = null;
        $editForm = null;
        $rateForm = null;
        if ($user != null) {
            $commentForm = $this->get('form.factory')->createNamed("commentForm", CommentType::class, $comment);
            $activateForm = $this->get('form.factory')->createNamed("activateForm", ActivateType::class, null, array(
                'disabled' => $workoutService->enableActivation($user, $workout, $request)
            ));
            $rateForm = $this->get('form.factory')->createNamed("rateForm", WorkoutRatingType::class, null);
        }
        if ($workoutService->canEdit($user, $workout)) {
            $editForm = $this->get('form.factory')->createNamed("editForm", WorkoutEditType::class, null);
        }
        if ($request->request->has("commentForm")) {
            $commentForm->handleRequest($request);
            if ($commentForm->isValid()) {
                $workoutService->commentWorkout($workout, $comment);
            }
        }
        if ($request->request->has("activateForm")) {
            $activateForm->handleRequest($request);
            if ($activateForm->isValid()) {
                $workoutService->activateWorkout($user, $workout);
            }
        }
        if ($request->request->has("rateForm")) {
            $rateForm->handleRequest($request);
            $workoutService->rateWorkout($user, $workout, $request->get("rating")->getData());
        }
        if ($request->request->has("editForm")) {
            $editForm->handleRequest($request);
            if ($request->request->has("delete")) {
                $workoutService->deleteWorkout($workout);
            } elseif ($request->request->has("edit") && $workoutService->canEdit($user, $workout)) {
                $this->redirect("../editWorkout/" . $workout->getId());
            }
            $workoutService->rateWorkout($user, $workout, $rateForm->get("rating")->getData());
        }

        return $this->render('@App/Home/queryWorkout.html.twig', array(
            "workout" => $workout,
            "form" => $commentForm->createView(),
            "formRate" => $rateForm->createView(),
            "activateForm" => $activateForm->createView()
        ));
    }
}
