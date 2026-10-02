<?php
class Contact extends Controller
{
    // /contact (or /kontakt) shows the form, /contact/send handles it
    public function showAction()
    {
        $this->title = t('contact.title');

        // old saved messages (personal data) are deleted now and then
        if (mt_rand(1, 20) === 1) {
            Mail::purgeOutbox();
        }

        // the moment the form was shown: a bot that posts directly or within a second is no visitor
        Session::set('contact_form_at', time());

        // messages and input from the last submit (set by sendAction before it redirected here)
        $this->data['sent'] = Session::getFlash('contact_sent', false);
        $this->data['failed'] = Session::getFlash('contact_failed', false);
        $this->data['errors'] = Session::getFlash('contact_errors', []);
        $this->data['old'] = Session::getFlash('contact_old', []);
    }

    public function sendAction()
    {
        $this->requirePost();       // the csrf token was already checked by the Router
        $request = $this->myrequest;

        // honeypot: the field "website" is hidden for people. only bots fill it. they get the same "thank you" and nothing is sent
        if ($request->getText('website') !== '') {
            Session::flash('contact_sent', true);
            $this->redirect(route('contact'));
        }

        $old = [
            'name'    => $request->getText('name'),
            'email'   => $request->getText('email'),
            'phone'   => $request->getText('phone'),
            'message' => $request->getText('message'),
        ];
        $errors = [];

        $shownAt = (int) Session::get('contact_form_at', 0);
        if ($shownAt === 0 || time() - $shownAt < 3) {
            $errors['form'] = t('contact.too_fast');
        }

        $validate = new Validate();
        $errors['name'] = $validate->ValidateText($old['name'], t('contact.name'), 2, 100);
        $errors['email'] = $validate->ValidateEmail($old['email']);
        $errors['message'] = $validate->ValidateText($old['message'], t('contact.message'), 10, 3000);
        $errors['phone'] = $validate->ValidatePattern($old['phone'], t('contact.phone_label'), '/^[0-9+()\/\-. ]+$/', 40);
        $errors = array_filter($errors);        // only the fields with a problem stay

        // the limit counts only valid messages, a visitor who mistyped isn't punished
        // ([security] limit_by_ip = 0 in config.ini: no IP address is used at all, only the overall limit below)
        if (!$errors && Config::bool('security.limit_by_ip', true) && !RateLimit::hit('contact:' . $request->getIp(), 5, 3600)) {
            $errors['form'] = t('contact.rate_limited');
        }
        // overall limit for the whole website (any visitors together): protects the mailbox when a botnet uses many addresses.
        // no personal data in this key
        if (!$errors && !RateLimit::hit('contact:all', 100, 3600)) {
            $errors['form'] = t('contact.rate_limited');
        }

        if ($errors) {
            Session::flash('contact_errors', $errors);
            Session::flash('contact_old', $old);
            $this->redirect(route('contact'));
        }

        $to = Config::get('mail.to') ?: Config::get('site.business.email');
        $lines = [
            t('contact.name') . ': ' . $old['name'],
            t('contact.email') . ': ' . $old['email'],
        ];
        if ($old['phone'] !== '') {
            $lines[] = t('contact.phone_label') . ': ' . $old['phone'];
        }
        $body = implode("\n", $lines) . "\n\n" . $old['message'] . "\n";
        $subject = t('mail.subject', ['site' => Config::get('site.name', ''), 'name' => $old['name']]);

        if (Mail::send($to, $subject, $body, $old['email'], $old['name'])) {
            Session::flash('contact_sent', true);
        } else {
            // never lose a customer's message: keep a copy where the owner can find it (storage/ is not reachable from the web)
            Mail::saveCopy($subject, $body);
            Session::flash('contact_failed', true);
            Session::flash('contact_old', $old);
        }
        $this->redirect(route('contact'));
    }
}
