<?php

namespace Bigfoot\Bundle\ImportBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Bigfoot\Bundle\ImportBundle\Entity\DataSource;
use Bigfoot\Bundle\ImportBundle\Form\DataSourceType;

/**
 * DataSource controller.
 *
 * @Route("/admin/datasource")
 */
class DataSourceController extends Controller
{

    /**
     * Lists all DataSource entities.
     *
     * @Route("/", name="admin_datasource")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('BigfootImportBundle:DataSource')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Creates a new DataSource entity.
     *
     * @Route("/", name="admin_datasource_create")
     * @Method("POST")
     * @Template("BigfootImportBundle:DataSource:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity  = new DataSource();
        $form = $this->createForm(new DataSourceType(), $entity);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_datasource_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Displays a form to create a new DataSource entity.
     *
     * @Route("/new", name="admin_datasource_new")
     * @Method("GET")
     * @Template()
     */
    public function newAction()
    {
        $entity = new DataSource();
        $form   = $this->createForm(new DataSourceType(), $entity);

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Finds and displays a DataSource entity.
     *
     * @Route("/{id}", name="admin_datasource_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BigfootImportBundle:DataSource')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DataSource entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing DataSource entity.
     *
     * @Route("/{id}/edit", name="admin_datasource_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BigfootImportBundle:DataSource')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DataSource entity.');
        }

        $editForm = $this->createForm(new DataSourceType(), $entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Edits an existing DataSource entity.
     *
     * @Route("/{id}", name="admin_datasource_update")
     * @Method("PUT")
     * @Template("BigfootImportBundle:DataSource:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('BigfootImportBundle:DataSource')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find DataSource entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(new DataSourceType(), $entity);
        $editForm->submit($request);

        if ($editForm->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_datasource_edit', array('id' => $id)));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a DataSource entity.
     *
     * @Route("/{id}", name="admin_datasource_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->submit($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('BigfootImportBundle:DataSource')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find DataSource entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_datasource'));
    }

    /**
     * Creates a form to delete a DataSource entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
