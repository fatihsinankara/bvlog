<?php

namespace App\Controllers\Admin;

use App\Models\CvEducation;
use App\Models\CvExperience;
use App\Models\CvSkill;
use Core\Controller;
use Core\Request;
use Core\Session;

class CvController extends Controller
{
    public function index(): void
    {
        $this->adminView('admin/cv/index', [
            'experiences' => CvExperience::ordered(),
            'educations'  => CvEducation::ordered(),
            'skills'      => CvSkill::ordered(),
            'pageTitle'   => 'CV & Özgeçmiş Yönetimi',
        ]);
    }

    // --- İŞ DENEYİMİ (EXPERIENCE) ---

    public function createExperience(): void
    {
        $this->adminView('admin/cv/experience-form', [
            'experience' => null,
            'pageTitle'  => 'Yeni İş Deneyimi Ekle',
        ]);
    }

    public function storeExperience(): void
    {
        $company = Request::post('company', '');
        $role = Request::post('role', '');
        if ($company === '' || $role === '') {
            Session::flash('error', 'Şirket adı ve pozisyon zorunludur.');
            $this->redirect('/admin/cv/experience/create');
        }

        CvExperience::create([
            'company'       => mb_substr($company, 0, 150),
            'role'          => mb_substr($role, 0, 150),
            'location'      => mb_substr(Request::post('location', ''), 0, 100),
            'start_date'    => mb_substr(Request::post('start_date', ''), 0, 50),
            'end_date'      => Request::post('is_current') ? null : mb_substr(Request::post('end_date', ''), 0, 50),
            'is_current'    => Request::post('is_current') ? 1 : 0,
            'description'   => mb_substr(Request::post('description', ''), 0, 2000),
            'display_order' => (int) Request::post('display_order', 1),
        ]);

        Session::flash('success', 'İş deneyimi eklendi.');
        $this->redirect('/admin/cv?tab=experience');
    }

    public function editExperience(string $id): void
    {
        $exp = CvExperience::find((int) $id);
        if (!$exp) {
            Session::flash('error', 'İş deneyimi bulunamadı.');
            $this->redirect('/admin/cv?tab=experience');
        }

        $this->adminView('admin/cv/experience-form', [
            'experience' => $exp,
            'pageTitle'  => 'İş Deneyimini Düzenle: ' . $exp['role'],
        ]);
    }

    public function updateExperience(string $id): void
    {
        $exp = CvExperience::find((int) $id);
        if (!$exp) {
            Session::flash('error', 'İş deneyimi bulunamadı.');
            $this->redirect('/admin/cv?tab=experience');
        }

        $company = Request::post('company', '');
        $role = Request::post('role', '');
        if ($company === '' || $role === '') {
            Session::flash('error', 'Şirket adı ve pozisyon zorunludur.');
            $this->redirect('/admin/cv/experience/' . $exp['id'] . '/edit');
        }

        CvExperience::updateById((int) $exp['id'], [
            'company'       => mb_substr($company, 0, 150),
            'role'          => mb_substr($role, 0, 150),
            'location'      => mb_substr(Request::post('location', ''), 0, 100),
            'start_date'    => mb_substr(Request::post('start_date', ''), 0, 50),
            'end_date'      => Request::post('is_current') ? null : mb_substr(Request::post('end_date', ''), 0, 50),
            'is_current'    => Request::post('is_current') ? 1 : 0,
            'description'   => mb_substr(Request::post('description', ''), 0, 2000),
            'display_order' => (int) Request::post('display_order', 1),
        ]);

        Session::flash('success', 'İş deneyimi güncellendi.');
        $this->redirect('/admin/cv?tab=experience');
    }

    public function deleteExperience(string $id): void
    {
        CvExperience::deleteById((int) $id);
        Session::flash('success', 'İş deneyimi silindi.');
        $this->redirect('/admin/cv?tab=experience');
    }

    // --- EĞİTİM GEÇMİŞİ (EDUCATION) ---

    public function createEducation(): void
    {
        $this->adminView('admin/cv/education-form', [
            'education' => null,
            'pageTitle' => 'Yeni Eğitim Bilgisi Ekle',
        ]);
    }

    public function storeEducation(): void
    {
        $institution = Request::post('institution', '');
        $degree = Request::post('degree', '');
        if ($institution === '' || $degree === '') {
            Session::flash('error', 'Okul/Kurum ve derece zorunludur.');
            $this->redirect('/admin/cv/education/create');
        }

        CvEducation::create([
            'institution'    => mb_substr($institution, 0, 150),
            'degree'         => mb_substr($degree, 0, 150),
            'field_of_study' => mb_substr(Request::post('field_of_study', ''), 0, 150),
            'start_date'     => mb_substr(Request::post('start_date', ''), 0, 50),
            'end_date'       => Request::post('is_current') ? null : mb_substr(Request::post('end_date', ''), 0, 50),
            'is_current'     => Request::post('is_current') ? 1 : 0,
            'description'    => mb_substr(Request::post('description', ''), 0, 2000),
            'display_order'  => (int) Request::post('display_order', 1),
        ]);

        Session::flash('success', 'Eğitim bilgisi eklendi.');
        $this->redirect('/admin/cv?tab=education');
    }

    public function editEducation(string $id): void
    {
        $edu = CvEducation::find((int) $id);
        if (!$edu) {
            Session::flash('error', 'Eğitim bilgisi bulunamadı.');
            $this->redirect('/admin/cv?tab=education');
        }

        $this->adminView('admin/cv/education-form', [
            'education' => $edu,
            'pageTitle' => 'Eğitim Bilgisini Düzenle: ' . $edu['institution'],
        ]);
    }

    public function updateEducation(string $id): void
    {
        $edu = CvEducation::find((int) $id);
        if (!$edu) {
            Session::flash('error', 'Eğitim bilgisi bulunamadı.');
            $this->redirect('/admin/cv?tab=education');
        }

        $institution = Request::post('institution', '');
        $degree = Request::post('degree', '');
        if ($institution === '' || $degree === '') {
            Session::flash('error', 'Okul/Kurum ve derece zorunludur.');
            $this->redirect('/admin/cv/education/' . $edu['id'] . '/edit');
        }

        CvEducation::updateById((int) $edu['id'], [
            'institution'    => mb_substr($institution, 0, 150),
            'degree'         => mb_substr($degree, 0, 150),
            'field_of_study' => mb_substr(Request::post('field_of_study', ''), 0, 150),
            'start_date'     => mb_substr(Request::post('start_date', ''), 0, 50),
            'end_date'       => Request::post('is_current') ? null : mb_substr(Request::post('end_date', ''), 0, 50),
            'is_current'     => Request::post('is_current') ? 1 : 0,
            'description'    => mb_substr(Request::post('description', ''), 0, 2000),
            'display_order'  => (int) Request::post('display_order', 1),
        ]);

        Session::flash('success', 'Eğitim bilgisi güncellendi.');
        $this->redirect('/admin/cv?tab=education');
    }

    public function deleteEducation(string $id): void
    {
        CvEducation::deleteById((int) $id);
        Session::flash('success', 'Eğitim bilgisi silindi.');
        $this->redirect('/admin/cv?tab=education');
    }

    // --- TEKNİK YETENEKLER (SKILL) ---

    public function createSkill(): void
    {
        $this->adminView('admin/cv/skill-form', [
            'skill'     => null,
            'pageTitle' => 'Yeni Teknik Yetenek Ekle',
        ]);
    }

    public function storeSkill(): void
    {
        $category = Request::post('category', 'Genel');
        $name = Request::post('name', '');
        if ($name === '') {
            Session::flash('error', 'Yetenek adı zorunludur.');
            $this->redirect('/admin/cv/skill/create');
        }

        $proficiency = max(10, min(100, (int) Request::post('proficiency', 85)));

        CvSkill::create([
            'category'      => mb_substr($category, 0, 100),
            'name'          => mb_substr($name, 0, 100),
            'proficiency'   => $proficiency,
            'display_order' => (int) Request::post('display_order', 1),
        ]);

        Session::flash('success', 'Yetenek eklendi.');
        $this->redirect('/admin/cv?tab=skills');
    }

    public function editSkill(string $id): void
    {
        $sk = CvSkill::find((int) $id);
        if (!$sk) {
            Session::flash('error', 'Yetenek bulunamadı.');
            $this->redirect('/admin/cv?tab=skills');
        }

        $this->adminView('admin/cv/skill-form', [
            'skill'     => $sk,
            'pageTitle' => 'Yeteneği Düzenle: ' . $sk['name'],
        ]);
    }

    public function updateSkill(string $id): void
    {
        $sk = CvSkill::find((int) $id);
        if (!$sk) {
            Session::flash('error', 'Yetenek bulunamadı.');
            $this->redirect('/admin/cv?tab=skills');
        }

        $category = Request::post('category', 'Genel');
        $name = Request::post('name', '');
        if ($name === '') {
            Session::flash('error', 'Yetenek adı zorunludur.');
            $this->redirect('/admin/cv/skill/' . $sk['id'] . '/edit');
        }

        $proficiency = max(10, min(100, (int) Request::post('proficiency', 85)));

        CvSkill::updateById((int) $sk['id'], [
            'category'      => mb_substr($category, 0, 100),
            'name'          => mb_substr($name, 0, 100),
            'proficiency'   => $proficiency,
            'display_order' => (int) Request::post('display_order', 1),
        ]);

        Session::flash('success', 'Yetenek güncellendi.');
        $this->redirect('/admin/cv?tab=skills');
    }

    public function deleteSkill(string $id): void
    {
        CvSkill::deleteById((int) $id);
        Session::flash('success', 'Yetenek silindi.');
        $this->redirect('/admin/cv?tab=skills');
    }
}
