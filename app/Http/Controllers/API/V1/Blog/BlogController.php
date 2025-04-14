<?php

namespace App\Http\Controllers\API\V1\Blog;

use App\Models\Blog\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Blog\AddBlogRequest;
use App\Http\Requests\Blog\UpdateBlogRequest;
use App\Http\Requests\Blog\GetBlogRequest;
use App\Http\Requests\UserManagement\UpdateOathControlRequest;
use App\Models\Blog\BlogCategory;

class BlogController extends Controller
{
  public function getBlog(GetBlogRequest $request)
  {
    try {
      if (isset($request->category_id)) {
        $category = $request->input('category_id');

        $blogs = Blog::where('categories', $category)->get();
        $count = Blog::where('categories', $category)->count();

        if ($blogs->isEmpty()) {
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blogs retrieved successfully', 'toast' => true, 'data' => ['count' => $count]]);
        }
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blogs retrieved successfully', 'toast' => true, 'data' => ['blogs' => $blogs->toArray(), 'count' => $count]]);
      } else {

        $id = $request->id;
        $blog = Blog::where('id', $id)->first();

        if (!$blog) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Blog with ID ' . $id . ' not found', 'toast' => true]);
        }
        if ($blog->blog_image) {
          $blog->blog_image = getFileTemporaryURL($blog->blog_image);
        }
        if ($blog->blog_detail_image) {
          $blog->blog_detail_image = getFileTemporaryURL($blog->blog_detail_image);
        }
        if ($blog->blog_video) {
          $blog->blog_video = getFileTemporaryURL($blog->blog_video);
        }

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blogs retrieved successfully', 'toast' => true, 'data' => ['blogs' => $blog->toArray()]]);
      }
    } catch (\Exception $e) {
      Log::error('Error while retrieving blogs: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true
      ]);
    }
  }

  public function getBlogList(Request $request)
  {
    try {
      $query = Blog::query();

      if ($request->filled('search')) {
        $searchTerm = $request->input('search');
        $query->where('title', 'LIKE', '%' . $searchTerm . '%');
      }

      $offset = $request->input('offset', 0);
      $limit = $request->input('limit', 10);

      $blogs = $query->offset($offset)->limit($limit)->get()->toArray();

      // Modify URLs for each blog
      $result = array();
      foreach ($blogs as $key => $blog) {
        if ($blog['blog_image']) {
          $blog['blog_image'] = getFileTemporaryURL($blog['blog_image']);
        }
        if ($blog['blog_detail_image']) {
          $blog['blog_detail_image'] = getFileTemporaryURL($blog['blog_detail_image']);
        }
        if ($blog['blog_video']) {
          $blog['blog_video'] = getFileTemporaryURL($blog['blog_video']);
        }
        $blog["blogs_id"] = $key + 1;
        $result[] = $blog;
      }


      $count = $query->count();

      if ($count <= 0) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No blog found matching the search criteria', 'toast' => true]);
      }

      return generateResponse([
        'type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blogs retrieved successfully', 'toast' => true, 'data' => $result, 'count' => $count,
      ]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving blogs: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true
      ]);
    }
  }
  public function updateBlog(UpdateBlogRequest $request)
  {

    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $blog_id = $request->blog_id;
      $userFolderIMG = "users/private/{$user->id}/blog/image";
      $userFolderVID = "users/private/{$user->id}/blog/video";
      $userFolderMP3 = "users/private/{$user->id}/blog/audio";

      Storage::makeDirectory($userFolderIMG);
      Storage::makeDirectory($userFolderVID);
      Storage::makeDirectory($userFolderMP3);

      $blog = Blog::where('id', $blog_id)->where('user_id', $user->id)->first();

      if (!$blog) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blog id not found ', 'toast' => true]);
      } else {
        if ($request->hasFile('blog_image') && $request->file('blog_image')->isValid()) {
          $imageName = time() . '.' . $request->blog_image->extension();
          $imagePath = $request->blog_image->storeAs($userFolderIMG, $imageName);

          if ($blog->blog_image) {
            Storage::delete(str_replace('storage/', '', $blog->blog_image));
          }

          $blog->blog_image =  $imagePath;
        }

        if ($request->hasFile('blog_detail_image') && $request->file('blog_detail_image')->isValid()) {
          $imageName = time() . '.' . $request->blog_detail_image->extension();
          $imagePath = $request->blog_detail_image->storeAs($userFolderIMG, $imageName);

          if ($blog->blog_detail_image) {
            Storage::delete(str_replace('storage/', '', $blog->blog_detail_image));
          }

          $blog->blog_detail_image =  $imagePath;
        }

        if ($request->hasFile('blog_video') && $request->file('blog_video')->isValid()) {
          $videoName = time() . '.' . $request->blog_video->extension();
          $videoPath = $request->blog_video->storeAs($userFolderVID, $videoName);
          // $blog->blog_video = $videoPath;

          if ($blog->blog_video) {
            Storage::delete(str_replace('storage/', '', $blog->blog_video));
          }

          $blog->blog_video =  $videoPath;
        }

        if ($request->hasFile('blog_audio') && $request->file('blog_audio')->isValid()) {
          // $audioName = time() . '.' . $request->blog_audio->extension();
          $audioName = $request->blog_audio->getClientOriginalName();
          $audioPath = $request->blog_audio->storeAs($userFolderMP3, $audioName);

          if ($blog->blog_audio) {
            Storage::delete(str_replace('storage/', '', $blog->blog_audio));
          }

          $blog->blog_audio =  $audioPath;
        }

        if (isset($request->title)) {
          $blog->title = $request->title;
        }

        if (isset($request->for_whom)) {
          $blog->for_whom = $request->for_whom;
        }

        if (isset($request->tags)) {
          $blog->tags = json_encode($request->tags);
        }

        if (isset($request->description)) {
          $blog->description = $request->description;
        }

        if (isset($request->type)) {
          $blog->type = $request->type;
        }

        if (isset($request->categories)) {
          $blog->categories = $request->categories;
        }

        if (isset($request->author)) {
          $blog->author = $request->author;
        } else {
          $blog->author = $user->username;
        }
        $blog->save();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blog updated successfully', 'toast' => true], ['Data' => $blog]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('Blog update error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function addBlog(AddBlogRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $userFolderIMG = "users/private/{$user->id}/blog/image";
      $userFolderVID = "users/private/{$user->id}/blog/video";
      $userFolderMP3 = "users/private/{$user->id}/blog/audio";

      Storage::makeDirectory($userFolderIMG);
      Storage::makeDirectory($userFolderVID);
      Storage::makeDirectory($userFolderMP3);

      $blog = new Blog();

      if ($request->hasFile('blog_image') && $request->file('blog_image')->isValid()) {
        $imageName = $request->file('blog_image')->getClientOriginalName();
        $imagePath = $request->blog_image->storeAs($userFolderIMG, $imageName);
        $blog->blog_image =  $imagePath;
      }

      if ($request->hasFile('blog_detail_image') && $request->file('blog_detail_image')->isValid()) {
        $detailImageName = $request->file('blog_detail_image')->getClientOriginalName();
        $detailImagePath = $request->blog_detail_image->storeAs($userFolderIMG, $detailImageName);
        $blog->blog_detail_image =  $detailImagePath;
      }
      if ($request->hasFile('blog_video') && $request->file('blog_video')->isValid()) {
        $videoName = $request->file('blog_video')->getClientOriginalName();
        $videoPath = $request->blog_video->storeAs($userFolderVID, $videoName);
        $blog->blog_video = $videoPath;
      }
      if ($request->hasFile('blog_audio') && $request->file('blog_audio')->isValid()) {
        $audioName = $request->file('blog_audio')->getClientOriginalName();
        $audioPath = $request->blog_audio->storeAs($userFolderMP3, $audioName);
        $blog->blog_audio = $audioPath;
      }

      $blog->user_id = $user->id;
      $blog->title = $request->title;
      // $blog->for_whom  = $request->for_whom;
      // $blog->tags = isset($request->tags) ? json_encode($request->tags) : null;
      $blog->description = $request->description;
      $blog->type  = $request->type;
      $blog->categories  = $request->categories;

      if ($request->author) {
        $blog->author = $request->author;
      } else {
        $blog->author = $user->username;
      }

      $blog->save();
      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blog added successfully', 'toast' => true], ['postData' => $blog]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('Blog add error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function deleteBlog(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $blog_id = $request->blog_id;

      $blog = Blog::where('id', $blog_id)->where('user_id', $user->id)->first();

      if ($blog) {

        $file = [];

        if (isset($blog->blog_image)) {
          $file[] = $blog->blog_image;
        }
        if (isset($blog->blog_detail_image)) {
          $file[] = $blog->blog_detail_image;
        }
        if (isset($blog->blog_video)) {
          $file[] = $blog->blog_detail_image;
        }
        if (count($file) > 0)
          Storage::delete($file);
        $blog->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Blog deleted successfully', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Blog not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('Blog delete error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function getAllCategories()
  {
    try {
      $firstRecord = Blog::orderBy('created_at')->first();
      $lastRecord = Blog::orderBy('created_at', 'desc')->first();

      $categories = ['TECH TRENDS', 'ARTIFICIAL INTELLIGENCE', 'PRODUCT REVIEWS', 'CLOUD COMPUTING', 'TECH NEWS', 'TECH CONFERENCES'];

      $categoryCounts = [];

      foreach ($categories as $category) {
        $count = Blog::where('categories', $category)
          ->whereBetween('created_at', [$firstRecord->created_at, $lastRecord->created_at])
          ->count();

        $categoryCounts[] = [
          'category' => $category,
          'count' => $count
        ];
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Categories count retrieved successfully',
        'toast' => true,
        'data' => $categoryCounts,
      ]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving categories: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true
      ]);
    }
  }
  public function addOrUpdateCategory(Request $request)
  {
      DB::beginTransaction();
      try {
          $user = $request->attributes->get('user');
          $category_id = $request->id;

          if ($category_id) {
              $categoryData = BlogCategory::find($category_id);
              if (!$categoryData) {
                  return generateResponse(['type' => 'error','code' => 404,'status' => false,'message' => 'Category not found','toast' => true ]);
              }

              $categoryData->category = $request->category;
              $categoryData->save();

              DB::commit();
              return generateResponse(['type' => 'success','code' => 200,'status' => true, 'message' => 'Category updated successfully','toast' => true], ['categoryData' => $categoryData]);

          } else {
              $categoryData = new BlogCategory();
              $categoryData->category = $request->category;
              $categoryData->save();

              DB::commit();
              return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Category added successfully', 'toast' => true ], ['categoryData' => $categoryData]);
          }

      } catch (\Exception $e) {
          DB::rollBack();
          Log::info('Category add/update error : ' . $e->getMessage());
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
      }
  }

  public function deleteCategory(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $category_id = $request->category_id;

      $categoryData = BlogCategory::where('id', $category_id)->first();

      if ($categoryData) {
        $categoryData->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Category deleted successfully', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Category not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('Category delete error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
