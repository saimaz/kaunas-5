<?php

namespace AppBundle\Repository;

use Doctrine\DBAL\Driver\Statement;
use \Doctrine\ORM\EntityRepository;

/**
 * CommentsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class WorkoutRepository extends EntityRepository
{
    /**
     * Po kiek uzkrauti workouts per viena uzklausa.
     */
    const PAGE_LENGTH = 4;
    /**
     * @param $page
     * @param $sort
     * @param $difficulty
     * @param $search
     * @param $type
     * @param $equipment
     * @param $muscle
     * @return mixed
     */
    public function getWorkouts($page, $sort, $difficulty, $search, $type, $equipment, $muscle)
    {
        $whereState="WHERE ";
        $sortState="Workouts." . $sort;
        $start = $page*self::PAGE_LENGTH;

        if ($search!=null) {
            $whereState = $whereState . "Workouts.title LIKE :search AND ";
        }

        if ($difficulty!=null) {
            $whereState = $whereState."(";
            foreach ($difficulty as $i) {
                $whereState = $whereState . "FIND_IN_SET(:difficulty" . $i . ", Workouts.difficulty) OR  ";
            }
            $whereState=substr($whereState, 0, -5);
            $whereState = $whereState.") AND ";
        }

        $whereState = $whereState . $this->searchTags($type, "type");
        $whereState = $whereState . $this->searchTags($equipment, "equipment");
        $whereState = $whereState . $this->searchTags($muscle, "muscle_group");

        if ($whereState=="WHERE ") {
            $whereState="";
        } elseif (substr($whereState, strlen($whereState)-5) == " AND ") {
            $whereState=substr($whereState, 0, -5);
        }

        $query = "SELECT Workouts.id,title, Workouts.rating,description, data_created, " .
            "Workouts.creator_id, Workouts.difficulty, username FROM Workouts " .
            "LEFT JOIN fos_user ON fos_user.id=Workouts.creator_id " . $whereState .
            " ORDER BY " . $sortState . " DESC LIMIT " . $start . "," . self::PAGE_LENGTH;

        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($query);
        if ($search!=null) {
            $stmt->bindValue('search', "%" . $search . "%");
        }

        $this->bindTags($type, "type", $stmt);
        $this->bindTags($equipment, "equipment", $stmt);
        $this->bindTags($muscle, "muscle_group", $stmt);

        /**
         * Funkcija tinka bindint ir difficulty array.
         */
        $this->bindTags($difficulty, "difficulty", $stmt);

        $stmt->execute();
        $workouts = $stmt->fetchAll();
        return $workouts;
    }

    /**
     * @param array $tags
     * @param string $tag_group
     * @return string
     */
    private function searchTags($tags, $tag_group)
    {
        $whereState = "";
        if ($tags != null) {
            foreach ($tags as $i) {
                $whereState = $whereState . "FIND_IN_SET(:" . $tag_group . $i . ", Workouts." . $tag_group . ") AND ";
            }
        }
        return $whereState;
    }

    /**
     * @param array $tags
     * @param string $tag_group
     * @param Statement $stmt
     */
    private function bindTags($tags, $tag_group, $stmt)
    {
        if ($tags != null) {
            foreach ($tags as $i) {
                $stmt->bindValue($tag_group.$i, $i);
            }
        }
    }
}