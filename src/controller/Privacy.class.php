<?php
class Privacy extends Controller
{
    public function showAction()
    {
        // text is in src/view/privacy/show.<language>.phtml, data comes from 'business' in config/site.php
        $this->title = t('footer.privacy');
        $this->localizedView('privacy/show');
    }
}
