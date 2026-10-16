<?php

namespace App\Controllers\Admin;

use App\Models\Category;
use Core\Controller;
use Core\Request;
use Core\Session;

class CategoryController extends Controller
{
    public function index(): void
    {
        $categories = Category::allWithCounts();

        $this->adminView('admin/categories/index', [
            'categories' => $categories,
            'pageTitle'  => 'Kategoriler',
        ]);
    }

    public function create(): void
    {
        $this->adminView('admin/categories/form', [
            'category'  => null,
            'pageTitle' => 'Yeni Kategori Ekle',
        ]);
    }

    public function store(): void
    {
        $name = Request::post('name', '');
        if ($name === '') {
            Session::flash('error', 'Kategori adı zorunludur.');
            $this->redirect('/admin/categories/create');
        }

        $customSlug = Request::post('slug', '');
        $slug = Category::ensureUniqueSlug(slugify($customSlug !== '' ? $customSlug : $name));

        $icon = Request::post('icon', 'fa-solid fa-folder');
        $color = Request::post('color', '#18181b');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#18181b';
        }

        Category::create([
            'name'        => mb_substr($name, 0, 100),
            'slug'        => $slug,
            'description' => mb_substr(Request::post('description', ''), 0, 255),
            'icon'        => mb_substr($icon, 0, 100),
            'color'       => $color,
            'image'       => Request::post('image', '') ?: null,
        ]);

        Session::flash('success', 'Kategori başarıyla eklendi.');
        $this->redirect('/admin/categories');
    }

    public function edit(string $id): void
    {
        $cat = Category::find((int) $id);
        if (!$cat) {
            Session::flash('error', 'Kategori bulunamadı.');
            $this->redirect('/admin/categories');
        }

        $this->adminView('admin/categories/form', [
            'category'  => $cat,
            'pageTitle' => 'Kategoriyi Düzenle: ' . $cat['name'],
        ]);
    }

    public function update(string $id): void
    {
        $cat = Category::find((int) $id);
        if (!$cat) {
            Session::flash('error', 'Kategori bulunamadı.');
            $this->redirect('/admin/categories');
        }

        $name = Request::post('name', '');
        if ($name === '') {
            Session::flash('error', 'Kategori adı zorunludur.');
            $this->redirect('/admin/categories/' . $cat['id'] . '/edit');
        }

        $customSlug = Request::post('slug', '');
        $slug = Category::ensureUniqueSlug(slugify($customSlug !== '' ? $customSlug : $name), (int) $cat['id']);

        $icon = Request::post('icon', 'fa-solid fa-folder');
        $color = Request::post('color', '#18181b');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#18181b';
        }

        Category::updateById((int) $cat['id'], [
            'name'        => mb_substr($name, 0, 100),
            'slug'        => $slug,
            'description' => mb_substr(Request::post('description', ''), 0, 255),
            'icon'        => mb_substr($icon, 0, 100),
            'color'       => $color,
            'image'       => Request::post('image', '') ?: null,
        ]);

        Session::flash('success', 'Kategori güncellendi.');
        $this->redirect('/admin/categories');
    }

    public function delete(string $id): void
    {
        Category::deleteById((int) $id);
        Session::flash('success', 'Kategori silindi.');
        $this->redirect('/admin/categories');
    }
}
