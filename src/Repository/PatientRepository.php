<?php

namespace App\Repository;

use App\Entity\Cabinet;
use App\Entity\Patient;
use App\Data\SearchData;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Patient|null find($id, $lockMode = null, $lockVersion = null)
 * @method Patient|null findOneBy(array $criteria, array $orderBy = null)
 * @method Patient[]    findAll()
 * @method Patient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PatientRepository extends ServiceEntityRepository
{
    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Patient::class);
        $this->paginator = $paginator;
    }

    /**
     * @return PaginationInterface
     */
    public function getAll(SearchData $data)
    {
        $entityManager= $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT p
            FROM App\Entity\Patient p
            ORDER BY p.id"
        );
        //return  $query->execute();
        return $this->paginator->paginate(
            $query,
            $data->page,
            10
    );
    }

    public function getByCabinet(Cabinet $cabinet)
    {
        $query = $this->createQueryBuilder("p")
                ->innerJoin("p.cabinet","c", "WITH","c.id= :id")
                ->setParameter("id",$cabinet);
        
        return $query->getQuery()->GetResult();
        /*$entityManager= $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT p
             FROM App\Entity\Patient p
             JOIN App\Entity\Cabinet c
             WHERE c.id = :id
             ORDER BY p.id"
        )->setParameter("id",1);
        return $query->execute();*/
    }

    public function getBySearch(SearchData $data)
    {
        $query = $this->createQueryBuilder("p");

        if(!empty($data->cabinets))
        {
            $ids = array_map(fn($cabinet) => $cabinet->getId(), $data->cabinets);
            $query = $query
                ->select("c","p")
                ->join("p.cabinet","c")
                ->andWhere($query->expr()->in("c.id", ":cabinetIds"))
                ->setParameter("cabinetIds", $ids);
        }
        else
            $query = $query->select("p");

        $patients = $query->getQuery()->getResult();

        if(!empty($data->q))
        {
            $needle = $this->normalizeForSearch($data->q);
            $patients = array_values(array_filter($patients, function(Patient $p) use ($needle) {
                foreach([$p->getNom(), $p->getPrenom(), $p->getNumFixe(), $p->getNumPortable(), $p->getCodePostal(), $p->getAdresse(), $p->getVille()] as $champ)
                    if($champ !== null && str_contains($this->normalizeForSearch($champ), $needle))
                        return true;
                return false;
            }));
        }

        return $this->paginator->paginate(
            $patients,
            $data->page,
            10
        );
    }

    /**
     * Met en minuscule et retire les accents pour permettre une recherche
     * insensible aux accents (ex: "elise" trouve "Élise") : SQLite n'a pas
     * de collation accent-insensible comme MySQL, la comparaison est donc
     * faite en PHP plutot qu'en SQL.
     */
    private function normalizeForSearch(string $value): string
    {
        $map = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a',
            'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ì' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ò' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'ÿ' => 'y', 'ñ' => 'n',
            'œ' => 'oe', 'æ' => 'ae',
        ];

        return strtr(mb_strtolower($value), $map);
    }

    public function getOneById($id)
    {
        $entityManager= $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT p 
             FROM App\Entity\Patient p
             WHERE p.id = :id"
        );
        $query->setParameter('id',$id);
        
        return $query->GetResult();
    }




    // /**
    //  * @return Patient[] Returns an array of Patient objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Patient
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
