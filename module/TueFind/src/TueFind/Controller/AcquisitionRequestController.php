<?php

namespace TueFind\Controller;

class AcquisitionRequestController extends \VuFind\Controller\AbstractBase
{
    /**
     * Show form to generate an acquisition request
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function createAction()
    {
        $view = $this->createViewModel();
        $view->useRecaptcha = $this->recaptcha()->active('acquisitionrequest');
        $id = $this->params()->fromQuery('id');
        if ($id == '') {
            $view->driver = null;
        } else {
            $recordLoader = $this->getRecordLoader();
            $view->driver = $recordLoader->load($id);
        }
        return $view;
    }

    /**
     * Process form, send mail
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function sendAction()
    {
        $config = $this->serviceLocator->get('VuFind\Config')->get('config');
        $view = $this->createViewModel();
        if ($this->params()->fromPost('submitted') != 'true') {
            $this->redirect()->toRoute('acquisitionrequest-create');
        } else {
            $to = $config->Site->acquisition_request_receivers;
            $from = $config->Site->email_from;
            $tuefind_type = ucfirst(basename(getenv('VUFIND_LOCAL_DIR')));
            $subject = $tuefind_type . '-Anschaffungsvorschlag';
            $body = "Folgender Vorschlag wurde über " . $tuefind_type . " eingereicht:\n";
            $body .= "\n";
            $body .= "Vorname: " . $this->params()->fromPost('firstname') . "\n";
            $body .= "Nachname: " . $this->params()->fromPost('lastname') . "\n";
            $body .= "E-Mail-Adresse: " . $this->params()->fromPost('email') . "\n";
            $body .= "\n";
            $body .= "Vorschlag:\n";
            $body .= $this->params()->fromPost('book') . "\n\n";
            $body .= "Kommentare:\n";
            $body .= $this->params()->fromPost('comments');
            $cc = null;
            $mailer = $this->serviceLocator->get('VuFind\Mailer');
            $mailer->setMaxRecipients(5);
            $mailer->send($to, $from, $subject, $body, $cc);
            $this->flashMessenger()->addMessage(
                'Thank you for your purchasing suggestion!', 'success'
            );
        }
    }
}