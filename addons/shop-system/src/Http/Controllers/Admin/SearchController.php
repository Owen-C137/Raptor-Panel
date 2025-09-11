<?php

namespace PterodactylAddons\ShopSystem\Http\Controllers\Admin;

use Illuminate\Http\Request;
use PterodactylAddons\ShopSystem\Http\Controllers\Controller;
use PterodactylAddons\ShopSystem\Models\ShopOrder;
use PterodactylAddons\ShopSystem\Models\ShopCategory;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

class SearchController extends Controller
{
    /**
     * Search for users
     */
    public function users(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }
        
        $users = User::where(function ($q) use ($query) {
                $q->where('email', 'like', "%{$query}%")
                  ->orWhere('username', 'like', "%{$query}%")
                  ->orWhere('name_first', 'like', "%{$query}%")
                  ->orWhere('name_last', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'email', 'username', 'name_first', 'name_last'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => "{$user->email} ({$user->username})",
                    'email' => $user->email,
                    'username' => $user->username,
                    'name' => trim($user->name_first . ' ' . $user->name_last)
                ];
            });
            
        return response()->json(['results' => $users]);
    }

    /**
     * Search for orders
     */
    public function orders(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }
        
        $orders = ShopOrder::with(['user', 'product'])
            ->where(function ($q) use ($query) {
                // Search by order ID
                if (is_numeric($query)) {
                    $q->where('id', $query);
                }
                
                // Search by user email/username
                $q->orWhereHas('user', function ($userQuery) use ($query) {
                    $userQuery->where('email', 'like', "%{$query}%")
                             ->orWhere('username', 'like', "%{$query}%");
                });
                
                // Search by product name
                $q->orWhereHas('product', function ($productQuery) use ($query) {
                    $productQuery->where('name', 'like', "%{$query}%");
                });
                
                // Search by transaction ID if it exists
                $q->orWhereHas('payments', function ($paymentQuery) use ($query) {
                    $paymentQuery->where('transaction_id', 'like', "%{$query}%");
                });
            })
            ->limit(20)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'text' => "Order #{$order->id} - {$order->user->email} - {$order->product->name}",
                    'user_email' => $order->user->email ?? 'N/A',
                    'product_name' => $order->product->name ?? 'N/A',
                    'status' => $order->status,
                    'amount' => $order->total_amount,
                    'created_at' => $order->created_at->format('Y-m-d H:i')
                ];
            });
            
        return response()->json(['results' => $orders]);
    }

    /**
     * Search for products
     */
    public function products(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }
        
        $categories = ShopCategory::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name', 'description', 'status'])
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'text' => $category->name,
                    'description' => $category->description,
                    'status' => $category->status
                ];
            });
            
        return response()->json(['results' => $categories]);
    }

    /**
     * Search for servers
     */
    public function servers(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }
        
        $servers = Server::where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('identifier', 'like', "%{$query}%");
            })
            ->with(['user', 'node'])
            ->limit(20)
            ->get()
            ->map(function ($server) {
                return [
                    'id' => $server->id,
                    'text' => "{$server->name} ({$server->identifier})",
                    'identifier' => $server->identifier,
                    'name' => $server->name,
                    'user_email' => $server->user->email ?? 'N/A',
                    'node_name' => $server->node->name ?? 'N/A',
                    'status' => $server->status
                ];
            });
            
        return response()->json(['results' => $servers]);
    }

    /**
     * Global search across multiple entities
     */
    public function global(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }
        
        $results = [
            'users' => [],
            'orders' => [],
            'products' => [],
            'servers' => []
        ];
        
        // Search users
        $users = User::where(function ($q) use ($query) {
                $q->where('email', 'like', "%{$query}%")
                  ->orWhere('username', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'email', 'username']);
            
        $results['users'] = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'type' => 'user',
                'text' => "{$user->email} ({$user->username})",
                'url' => route('admin.users.view', $user->id)
            ];
        });
        
        // Search orders
        $orders = ShopOrder::with(['user', 'product'])
            ->where('id', 'like', "%{$query}%")
            ->orWhereHas('user', function ($userQuery) use ($query) {
                $userQuery->where('email', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();
            
        $results['orders'] = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'type' => 'order',
                'text' => "Order #{$order->id} - {$order->user->email}",
                'url' => route('admin.shop.orders.show', $order->id)
            ];
        });
        
        // Search categories
        $categories = ShopCategory::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name']);
            
        $results['categories'] = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'type' => 'category',
                'text' => $category->name,
                'url' => route('admin.shop.categories.show', $category->id)
            ];
        });
        
        return response()->json($results);
    }
}
