<?php
/**
 * Created by: Sanija K
 * User : vinam
 * Date : 4-04-2019 09:30 AM
 */
namespace App\Controller;

use App\Constants\CommonConstants;
use App\Controller\MainDnsController;
use App\Entity\DnsGroup;
use App\Entity\DnsHistory;
use App\Entity\DnsRecord;
use App\Entity\User;
use App\Service\DnsService;
use App\Service\EmailService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use \DateTime;

class DnsRecordController extends MainDnsController
{

    /**
     * @param  SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        parent::__construct($session);
    }

    /**
     * @Route("/")
     * @Route("/home/{grpId}", name="home")
     * @param int $grpId
     * @param  Request $request
     * @param PaginatorInterface $paginator
     */

    public function manageDns($grpId = 0, Request $request, PaginatorInterface $paginator)
    {
        $dataRecord = array();
        $flag = 0;
        $username = '';
        $successCount = 0;
        $failureCount = 0;
        $em = $this->getDoctrine()->getManager();
        $this->setPublicValues($request);
        $userid = $this->userId;
        $repository = $em->getRepository(DnsGroup::class);
        $groupId = $repository->findOneBy(['id' => $grpId, 'status' => 1]);
        $records = $repository->findByStatus($userid);
        $result = $paginator->paginate(
            $records,
            $request->query->getInt('page', 1),
            5
        );
        $id = array();
        $data = $result->getItems();
        foreach ($data as $key => $value) {
            $id[] = $value['id'];
        }
        $recCount = $em->getRepository(DnsRecord::class)->recordCount($id);
        $rec = [];
        foreach ($recCount as $value) {
            $rec[$value['dnsGroupId']] = true;
            if ($value['result'] == 2) {
                $rec[$value['dnsGroupId']] = false;
            }
        }
        $res = $em->getRepository(DnsRecord::class)->successRecordCount($grpId);
        $returnData = $this->recordVerified($res);
        $successCount = $returnData['successCount'];
        $failureCount = $returnData['failureCount'];
        if ($grpId != 0) {
            $record = $this->getDoctrine()->getRepository(DnsRecord::class)->findBy(['dnsGroupId' => $grpId, 'status' => 1]);
            foreach ($record as $key => $value) {
                $value = $value->toArray();
                if ($value['type'] == 'A') {
                    $dataRecord['A'][] = $value;
                } else if ($value['type'] == 'TXT') {
                    $dataRecord['TXT'][] = $value;
                } else if ($value['type'] == 'MX') {
                    $dataRecord['MX'][] = $value;
                } else if ($value['type'] == 'CNAME') {
                    $dataRecord['CNAME'][] = $value;
                } else {
                    $dataRecord['PTR'][] = $value;
                }
            }
            if (!empty($groupId)) {
                if ($groupId->getPrivacy() == 0) {
                    if (empty($this->getUser())) {
                        return $this->redirectToRoute('login');
                    } else {
                        $flag = 1;
                        $username = $this->userName;
                    }
                } else {
                    $flag = 1;
                    $username = $this->userName;
                    if (empty($this->getUser())) {
                        $username = "Guest User";
                    }
                }
            } else {
                return new Response("Sorry!!! We are unable to service your request.....");
            }
            if ($flag) {
                return $this->render('viewdns.html.twig', [
                    'loggedinuser' => $username,
                    'dnsRecord' => $dataRecord,
                    'grpName' => $groupId->getName(),
                    'createdDate' => $groupId->getDatecreated(),
                    'successCount' => $successCount,
                    'failureCount' => $failureCount,
                ]);
            }
        }
        $rep = $this->getDoctrine()->getRepository(DnsRecord::class);
        $res = $rep->findCount($id);
        return $this->render('dnslist.html.twig', [
            'loggedinuser' => $this->userName,
            'result' => $result,
            'res' => $res,
            'grpId' => $grpId,
            'verificationStatus' => $rec,
        ]);
    }

    /**
     * @Route("/insertRecord", name="insertRecord")
     * @param  Request $request
     * @return  JsonResponse
     */
    public function insertRecord(Request $request): JsonResponse
    {
        $response = [];
        try {
            $this->setPublicValues($request);
            $entityManager = $this->getDoctrine()->getManager();
            $formdata = $request->request->all();
            $obj = new DnsRecord();
            $formdata['dateCreated'] = new DateTime();
            $formdata['dateModified'] = new DateTime();
            $formdata['userid'] = $this->userId;
            $formdata['priority'] = 1;
            $formdata['status'] = 1;
            $formdata['result'] = 2;
            $chkDuplicate = $entityManager->getRepository(DnsRecord::class)->findBy(['name' => $formdata['name'], 'value' => $formdata['value'], 'type' => $formdata['type'], 'status' => 1, 'dnsGroupId' => $formdata['dnsGroupId']]);
            if ($chkDuplicate == null) {
                $dnsGroup = $entityManager->getRepository(DnsGroup::class)->insertData($formdata, $obj);
                $response = ['status' => 'Success'];
            } else {
                $response = ['status' => 'Failure'];
            }
        } catch (Exception $e) {
            $response = ['status' => 'Exception'];
        }
        $entry = $entityManager->getRepository(DnsRecord::class)->findBy(['status' => 1, 'dnsGroupId' => $formdata['dnsGroupId']]);
        foreach ($entry as $value) {
            $value = $value->toArray();
            if ($value['type'] == 'A') {
                $array_Data['A'][] = $value;
            } else if ($value['type'] == 'TXT') {
                $array_Data['TXT'][] = $value;
            } else if ($value['type'] == 'MX') {
                $array_Data['MX'][] = $value;
            } else if ($value['type'] == 'CNAME') {
                $array_Data['CNAME'][] = $value;
            } else {
                $array_Data['PTR'][] = $value;
            }
        }
        $template = $this->get('twig')->render('edithtml.html.twig', ['dnsRecord' => $array_Data]);
        $response['dnsRecord'] = $template;
        return new JsonResponse($response);
    }

    /**
     * @Route("/insertdns", name="insertdns")
     * @param  Request $request
     */
    public function insertDns(Request $request)
    {
        $this->setPublicValues($request);
        $entityManager = $this->getDoctrine()->getManager();
        $formdata = $request->request->all();
        $obj = new DnsGroup();
        if (isset($_POST['save'])) {
            $formdata['datecreated'] = new DateTime();
            $formdata['datemodified'] = new DateTime();
            $formdata['userid'] = $this->userId;
            $formdata['status'] = 1;
            $formdata['name'] = trim($formdata['name']);
            $chkDuplicate = $entityManager->getRepository(DnsGroup::class)->findOneBy(['name' => $formdata['name'], 'status' => $formdata['status']]);
            if ($chkDuplicate == null) {
                $dnsGroup = $entityManager->getRepository(DnsGroup::class)->insertData($formdata, $obj);
            }
            return $this->redirectToRoute('home');
        }
    }
    /**
     * @Route("/createdns", name="createdns")
     * @param  Request $request
     */
    public function createDns(Request $request)
    {
        $this->setPublicValues($request);
        return $this->render('createdns.html.twig', ['loggedinuser' => $this->userName]);
    }

    /**
     * @Route("/deleteGroup", name="deleteGroup")
     * @param  Request $request
     * @return  JsonResponse
     */
    public function deleteEntry(Request $request): JsonResponse
    {
        $response = [];
        $id = $request->request->get('id');
        $em = $this->getDoctrine()->getManager();
        $chkEntry = $em->getRepository(DnsGroup::class)->findOneBy(['id' => $id, 'status' => 1]);
        if ($chkEntry != null) {
            $entry = $em->getRepository(DnsGroup::class)->updateGrpStatus($id);
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'failure'];
        }
        return new JsonResponse($response);
    }

    /**
     * @Route("/recordChanged", name="recordChanged")
     * @param Request $request
     * @return  JsonResponse
     */
    public function recordChanged(Request $request): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        $formdata = $request->request->all();
        $formdata['dateModified'] = new DateTime();
        $entry = $entityManager->getRepository(DnsRecord::class)->updateRecord($formdata['id'], $formdata['value'], $formdata['type'], $formdata['dateModified']);
        $response = ['status' => 'success'];
        return new JsonResponse($response);
    }

    /**
     * @Route("/recheckRecord", name="recheckRecord")
     * @param Request $request
     * @param DnsService $recheckRecord
     * @param EmailService $emailService
     * @return  JsonResponse
     */
    public function recheckRecord(Request $request, DnsService $recheckRecord, EmailService $emailService): JsonResponse
    {
        $flag = 0;
        $email = "not sent";
        $returnValue = 0;
        $response = [];
        $this->setPublicValues($request);
        $userid = $this->userId;
        $recheckData = $request->request->all();
        $recheckData['notification'] = empty($recheckData['notification']) ? "false" : $recheckData['notification'];
        $em = $this->getDoctrine()->getManager();
        $record = $em->getRepository(DnsRecord::class)->findOneBy(['id' => $recheckData['id']]);
        $data = $record->toArray();
        $obj = new DnsHistory();
        $returnValue = $recheckRecord->getDNSRecord($data);
        if ($returnValue) {
            $data['dnsRecordId'] = $data['id'];
            $data['result'] = CommonConstants::dnsSuccess;
            $data['dateTime'] = new DateTime();
            $res = $em->getRepository(DnsRecord::class)->updateData($data['result'], $data['dnsRecordId']);
            if ($recheckData['notification'] == "true") {
                $res = $em->getRepository(DnsRecord::class)->successRecordCount($recheckData['dnsGrpId']);
                for ($i = 0; $i < sizeof($res); $i++) {
                    $records = $res[$i];
                    if (($records['result'] == 1) && (sizeof($res) == 1)) {
                        $flag = 1;
                    }
                }
                if ($flag) {
                    $emailDetails = $em->getRepository(User::class)->findOneBy(['id' => $userid]);
                    $emailDetails = $emailDetails->toArray();
                    $sent = $emailService->sendMail($emailDetails, 'All entered records are verified', 'Email Notification');
                    $email = "sent";
                }
            }
            $result = $em->getRepository(DnsGroup::class)->insertData($data, $obj);
            $response = ['status' => 'verified', 'check' => true, 'email' => $email];
        } else {
            $data['dnsRecordId'] = $data['id'];
            $data['result'] = CommonConstants::dnsfailure;
            $data['dateTime'] = new DateTime();
            $result = $em->getRepository(DnsGroup::class)->insertData($data, $obj);
            $res = $em->getRepository(DnsRecord::class)->updateData($data['result'], $data['dnsRecordId']);
            $response = ['status' => 'not verified', 'check' => false, 'email' => $email];
        }
        $res = $em->getRepository(DnsRecord::class)->successRecordCount($recheckData['dnsGrpId']);
        $returnData = $this->recordVerified($res);
        $response['successCount'] = $returnData['successCount'];
        $response['failureCount'] = $returnData['failureCount'];
        return new JsonResponse($response);
    }

    /**
     * @Route("/editdns/{grpId}", name="editdns")
     * @param int $grpId
     * @param Request $request
     */
    public function editDns($grpId = 0, Request $request)
    {
        $this->setPublicValues($request);
        $userid = $this->userId;
        if ($grpId != 0) {
            $em = $this->getDoctrine()->getManager();
            $entry = $em->getRepository(DnsRecord::class)->findBy(['dnsGroupId' => $grpId, 'status' => 1]);
            $group = $em->getRepository(DnsGroup::class)->findOneBy(['id' => $grpId, 'status' => 1]);
            $group = $group->toArray();
            foreach ($entry as $key => $value) {
                $value = $value->toArray();
                if ($value['type'] == 'A') {
                    $data['A'][] = $value;
                } else if ($value['type'] == 'TXT') {
                    $data['TXT'][] = $value;
                } else if ($value['type'] == 'MX') {
                    $data['MX'][] = $value;
                } else if ($value['type'] == 'CNAME') {
                    $data['CNAME'][] = $value;
                } else {
                    $data['PTR'][] = $value;
                }
            }
            $data['privacy'] = $group['privacy'];
            if ($data['privacy'] == 0 && empty($this->getUser())) {
                $data = [];
            }
            $data['notification'] = $group['notification'];
            $data['name'] = $group['name'];
            $data['datecreated'] = $group['datecreated'];
            $res = $em->getRepository(DnsRecord::class)->successRecordCount($grpId);
            $returnData = $this->recordVerified($res);
            $template = $this->get('twig')->render('edithtml.html.twig', ['dnsRecord' => $data]);
            return $this->render('editdns.html.twig', [
                'loggedinuser' => $this->userName,
                'template' => $template,
                'dnsGroupId' => $grpId,
                'grpName' => $data['name'],
                'datecreated' => $data['datecreated'],
                'privacy' => $data['privacy'],
                'notification' => $data['notification'],
                'faliure' => $returnData['failureCount'],
                'success' => $returnData['successCount'],
            ]);
        }
    }

    /**
     * @Route("/updateprivacyemail", name="updateprivacyemail")
     * @param  Request $request
     * @return  JsonResponse
     */

    public function updateprivacyemail(Request $request): JsonResponse
    {
        $entityManager = $this->getDoctrine()->getManager();
        $data = $request->request->all();
        $data['datemodified'] = new DateTime();
        $data['privacy'] = ($data['privacy'] == "true") ? 1 : 0;
        $data['notification'] = ($data['notification'] == "true") ? 1 : 0;
        $entry = $entityManager->getRepository(DnsGroup::class)->updateemailprivacy($data);
        $response = ['Status' => $entry];
        return new JsonResponse($response);
    }

    /**
     * @Route("/deleteMultipleRecord", name="deleteMultipleRecord")
     * @param  Request $request
     * @return  JsonResponse
     */
    public function deleteMultipleRecordEntry(Request $request): JsonResponse
    {
        $this->setPublicValues($request);
        $userid = $this->userId;
        $id = $request->request->get('id');
        $grpId = $request->request->get('dnsGrpId');
        $em = $this->getDoctrine()->getManager();
        foreach ($id as $ids) {
            $entry = $em->getRepository(DnsRecord::class)->updateStatus($ids);
        }
        $res = $em->getRepository(DnsRecord::class)->successRecordCount($grpId);
        $returnData = $this->recordVerified($res);
        $response = ['status' => 'success', 'data' => $entry, 'successCount' => $returnData['successCount'], 'failureCount' => $returnData['failureCount']];
        return new JsonResponse($response);
    }

    /**
     * @Route("/recordHistory/{recId}", name="recordHistory")
     * @param int $recId
     * @param  Request $request
     * @return  JsonResponse
     */
    public function recordHistory($recId = 0, Request $request)
    {
        $this->setPublicValues($request);
        $em = $this->getDoctrine()->getManager();
        $entry = $em->getRepository(DnsHistory::class)->findRecordHistory($recId);
        return $this->render('history.html.twig', [
            'loggedinuser' => $this->userName,
            'history' => $entry,
        ]);
    }

    /**
     * @Route("/deleteRecord", name="deleteRecord")
     * @param Request $request
     * @return  JsonResponse
     */
    public function deleteRecordEntry(Request $request): JsonResponse
    {
        $this->setPublicValues($request);
        $userid = $this->userId;
        $id = $request->request->get('id');
        $grpId = $request->request->get('dnsGrpId');
        $em = $this->getDoctrine()->getManager();
        $chkEntry = $em->getRepository(DnsRecord::class)->findOneBy(['id' => $id]);
        if ($chkEntry != null) {
            $entry = $em->getRepository(DnsRecord::class)->updateStatus($id);
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'failure'];
        }
        $res = $em->getRepository(DnsRecord::class)->successRecordCount($grpId);
        $returnData = $this->recordVerified($res);
        $response['successCount'] = $returnData['successCount'];
        $response['failureCount'] = $returnData['failureCount'];
        return new JsonResponse($response);
    }

    /**
     * @param array $res
     * @return array
     */
    public function recordVerified($res)
    {
        $successCount = 0;
        $failureCount = 0;
        for ($i = 0; $i < sizeof($res); $i++) {
            $records = $res[$i];
            if ($records['result'] == 1) {
                $successCount = $records['count'];
            }
            if ($records['result'] == 2) {
                $failureCount = $records['count'];
            }
        }
        $returnData = ['successCount' => $successCount, 'failureCount' => $failureCount];
        return $returnData;
    }
}
