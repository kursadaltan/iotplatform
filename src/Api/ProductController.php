<?php

declare(strict_types=1);

namespace IoT\Api;

use \Iot\System\Helpers\Helper;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Exception;
use DI\Container;
use GuzzleHttp\Psr7\ServerRequest;
use IoT\Api\SessionController;

use \IoT\System\Helpers\JWTDecode;
use PDOException;
use stdClass;


Class ProductController 
{
  
    private $container;
    private $superAdmin = false;
    private $database;
    private $session;

    private $pageLimit = 25;
    private $activePage = 1;

    private $order_by = "productID";
    private $sort_by = "desc";
    private $groupID;
    private $productID;
    private $search = null;
    private $pagination = true;
    private $brand = null;
    private $group_ownerID;
    private $groupSub = true;
    private $code;
    private $helper;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->database = $this->container->get("connection");        
        $this->session = new SessionController($container);
        
    }

    public function getProducts(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->checkParams($request->getQueryParams(), $args);
        $this->session->authUser = $this->session->jwtUser($request);     
        $totalRecord = 0;          
        $check = $this->permissionCheck("products.index", $response);                
        if(!is_object($check)){           
            $db = $this->database; 
            $db->table('products')           
            ->orderBy("$this->order_by $this->sort_by")       
            ->cache(60);  
            if(!empty($this->productID)){
                $db->where('productID', $this->productID);
            }  
            if(!empty($this->groupID)){
                $db->where('device_groupID', $this->groupID);
            }
            if(!empty($this->brand)){
                $db->where('productBrand', $this->brand);
            }            
            if(!empty($this->search)){  
                $db->grouped(function($q){
                    $q->like('productName', "%$this->search%");
                    $q->orLike("productTags", "%$this->search%");                    
                });
            }
            if(!$this->superAdmin){               
                $db->where('productStatus', 1);        
            }
           
            $products = $db->getAll();                 
            $totalRecord = $db->numRows();    
            $responseProducts = array();

            if($this->pagination){          
                $totalPage = ceil($totalRecord / $this->pageLimit);
                if($this->activePage > $totalPage) $this->activePage = $totalPage;                
                $responseProducts['totalPage'] = $totalPage;
                $responseProducts['activePage'] = $this->activePage;
                $responseProducts['totalRecord'] = $totalRecord;
                if($this->activePage>1) $responseProducts['previousPage'] = $this->previousPage($request);
                if($this->activePage < $totalPage) $responseProducts['nextPage'] = $this->nextPage($request);         
                $responseProducts['data'] = array();
                $start = ($this->activePage-1) * $this->pageLimit;
                for($i=($start); $i<($start+ $this->pageLimit); $i++){
                    if(isset($products[$i])){
                        array_push($responseProducts['data'], $products[$i]);
                    }
                }     
            }  
            else
            {
                $responseProducts = $products;
            }       
            $return =  $this->session->prepareResponse($responseProducts);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Products can\'t found';   
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);         
            }       
        }
        else
        {
            return $check;
        }       
    }

    public function getProductTable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->checkParams($request->getQueryParams(), $args);
        $this->session->authUser = $this->session->jwtUser($request);     
        $totalRecord = 0;   
        $check = $this->permissionCheck("products.index", $response);
        $id = $args['product'];
        $id = (int)$id;        
        $yetki = false;
        
        if(!is_object($check)){           
            $db = $this->database; 
            $db->table('product_table')           
            ->orderBy("tableID desc")       
            ->cache(20)
            ->where('table_productID', $id);

            if(!$this->superAdmin){
                $db->where("productStatus", 1);         
            }
           
            $products = $db->getAll(); 
            $responseProducts = array();
            $responseProducts = $products;
                 
            $return =  $this->session->prepareResponse($responseProducts);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Product can\'t found';   
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);         
            }       
        }
        else
        {
            return $check;
        }       
    }

    public function newProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterProduct($input);     
            if(!$data){
                $return['message'] = 'Product can\'t added, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                try
                {
                    $add = $db->table('products')->insert($data);
                    $data['productID'] = $add;
                    $return['message'] = 'OK';
                    $return['data'] = $data;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(201);    
                }
                catch(PDOException $ex)
                {
                    $return['message'] = 'ERROR';
                    $return['data'] = $ex->errorInfo;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);   
                }
            }        
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }
    public function updateProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.update", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['product'];
            $id = (int)$id;        
            $yetki = false;

            $productDetail = $db->table('products')
            ->where('productID', $id)
            ->getAll();

            if(!$productDetail){
                $return['message'] = 'Product can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }                        
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterProduct($input);     
            if(!$data){
                $return['message'] = 'Product can\'t updated, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                try
                {
                    $data['productUpdated_at'] = date('Y-m-d H:i:s');  
                    $data['productID'] = $id; 
                    if($data['productStatus'] == 1) $data['productDeleted_at'] = NULL;                                      
                    $db->table('products')->where('productID', $id)->update($data);                       
                    $return['message'] = 'OK';
                    $return['data'] = $data;                       
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);    
                }
                catch(PDOException $ex)
                {
                    $return['message'] = 'ERROR';
                    $return['data'] = $ex->errorInfo;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);   
                }
            }                             
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }
    public function deleteProduct(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.delete", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['product'];
            $id = (int)$id;        
            $yetki = false;

            $productDetail = $db->table('products')
            ->where('productID', $id)
            ->getAll();

            if(!$productDetail){
                $return['message'] = 'Product can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }             
            try
            {
                $data['productUpdated_at'] = date('Y-m-d H:i:s');  
                $data['productDeleted_at'] = date('Y-m-d H:i:s');
                $data['productStatus'] = 0;
                $data['productID'] = $id;                      
                $update = $db->table('products')->where('productID', $id)->update($data);                       
                $return['message'] = 'OK';                                    
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);    
            }
            catch(PDOException $ex)
            {
                $return['message'] = 'ERROR';
                $return['data'] = $ex->errorInfo;
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);   
            }          
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function newProductTable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterProductTable($input);     
            
            if(!$data){
                $return['message'] = 'Product Table can\'t added, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                    try
                    {
                        $add = $db->table('product_table')->insert($data);
                        $data['tableID'] = $add;
                        $return['message'] = 'OK';
                        $return['data'] = $data;
                        $response->getBody()->write($this->session->prepareResponse($return));
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(201);    
                    }
                    catch(PDOException $ex)
                    {
                        $return['message'] = 'ERROR';
                        $return['data'] = $ex->errorInfo;
                        $response->getBody()->write($this->session->prepareResponse($return));
                        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(403);   
                    }
            }        
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function updateProductTable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.update", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['table'];
            $id = (int)$id;       
            $tableDetail = $db->select('tableID, table_productID')
            ->table('product_table')
            ->where('tableID', $id)
            ->getAll();

            if(!$tableDetail){
                $return['message'] = 'Table can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $productDetail = $db->table('products')
            ->where('productID', $tableDetail[0]->table_productID)
            ->getAll();

            if(!$productDetail){
                $return['message'] = 'Product can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterProductTable($input);     
            if(!$data){
                $return['message'] = 'Table can\'t updated, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                try
                {
                    $data['tableID'] = $id;       
                    $db->table('product_table')->where('tableID', $id)->update($data);                       
                    $return['message'] = 'OK';
                    $return['data'] = $data;                       
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);    
                }
                catch(PDOException $ex)
                {
                    $return['message'] = 'ERROR';
                    $return['data'] = $ex->errorInfo;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);   
                }
            }                            
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function deleteProductTable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.delete", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['table'];
            $id = (int)$id;    

            $tableDetail = $db->select('tableID, table_productID')
            ->table('product_table')
            ->where('tableID', $id)
            ->getAll();

            if(!$tableDetail){
                $return['message'] = 'Table can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $productDetail = $db->table('products')
            ->where('productID', $tableDetail[0]->table_productID)
            ->getAll();

            if(!$productDetail){
                $return['message'] = 'Product can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }              
            try
            {
                $db->table('product_table')->where('tableID', $id)->delete();                       
                $return['message'] = 'OK';                                          
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);    
            }
            catch(PDOException $ex)
            {
                $return['message'] = 'ERROR';
                $return['data'] = $ex->errorInfo;
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);   
            }
                           
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }


    public function getProductGroups(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->checkParams($request->getQueryParams(), $args);
        $this->session->authUser = $this->session->jwtUser($request);     
        $totalRecord = 0;   
        $this->order_by = "groupID";
        $check = $this->permissionCheck("products.groups.index", $response);                
        if(!is_object($check)){           
            $db = $this->database; 
            $db->table('product_groups')           
            ->orderBy("$this->order_by $this->sort_by")       
            ->cache(60); 
            if(!empty($this->id)){
                $db->where('groupID', $this->id);
            }      
            if(!empty($this->search)){  
                $db->grouped(function($q){
                    $q->like('groupName', "%$this->search%");
                });
            }
            if(!empty($this->group_ownerID)){
                $db->where('group_ownerID', $this->group_ownerID);
                $this->groupSub = false;
            } 
            if(!$this->superAdmin){               
                $db->where('groupStatus', 1);        
            }                       
            $responseProducts = $db->getAll();  
            if($this->groupSub){
                $list = array();    
                foreach($responseProducts as $group){
                    $list[$group->groupID] = $group;    
                }              
                if($this->groupID > 0){
                    $tree = $list[$this->groupID];
                    $tree->subGroup = $this->buildTree($list, $this->groupID);
                }
                else
                {
                    $tree = $this->buildTree($list, 0);
                }              
                
                $responseProducts = $tree;
            }

            $return =  $this->session->prepareResponse($responseProducts);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Groups can\'t found';   
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);         
            }       
        }
        else
        {
            return $check;
        }       
    }
    
    public function newProductGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.groups.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterProductGroup($input);     
            if(!$data){
                $return['message'] = 'Group can\'t added, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                try
                {
                    $add = $db->table('product_groups')->insert($data);
                    $data['groupID'] = $add;
                    $return['message'] = 'OK';
                    $return['data'] = $data;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(201);    
                }
                catch(PDOException $ex)
                {
                    $return['message'] = 'ERROR';
                    $return['data'] = $ex->errorInfo;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);   
                }
            }        
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function updateProductGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.groups.update", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['group'];
            $id = (int)$id;       
            $groupDetail = $db->table('product_groups')
            ->where('groupID', $id)
            ->getAll();

            if(!$groupDetail){
                $return['message'] = 'Group can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterProductGroup($input);     
            if(!$data){
                $return['message'] = 'Group can\'t updated, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                try
                {
                    $data['groupID'] = $id;       
                    $db->table('product_groups')->where('groupID', $id)->update($data);                       
                    $return['message'] = 'OK';
                    $return['data'] = $data;                       
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);    
                }
                catch(PDOException $ex)
                {
                    $return['message'] = 'ERROR';
                    $return['data'] = $ex->errorInfo;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);   
                }
            }                            
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function deleteProductGroup(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("products.groups.update", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['group'];
            $id = (int)$id;       
            $groupDetail = $db->table('product_groups')
            ->where('groupID', $id)
            ->getAll();

            if(!$groupDetail){
                $return['message'] = 'Group can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }
            try
            {
                $data['groupStatus'] = $id; 
                $data['groupDeleted_at'] = date('Y-m-d H:i:s');      
                $db->table('product_groups')->where('groupID', $id)->update($data);                       
                $return['message'] = 'OK';                          
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);    
            }
            catch(PDOException $ex)
            {
                $return['message'] = 'ERROR';
                $return['data'] = $ex->errorInfo;
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);   
            }                                      
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function getLabels(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {        
        $this->checkParams($request->getQueryParams(), $args);
        $this->session->authUser = $this->session->jwtUser($request);     
        $totalRecord = 0;   
        $this->order_by = "labelID";
        $check = $this->permissionCheck("labels.index", $response);                
        if(!is_object($check)){           
            $db = $this->database; 
            $db->table('labels')           
            ->orderBy("$this->order_by $this->sort_by")       
            ->cache(60); 
            if(!empty($this->id)){
                $db->where('labelID', $this->id);
            }   
            if(!empty($this->code)){
                $db->where('labelCode', $this->code);
            }  
            if(!empty($this->unit)){
                $db->where('labelUnit', $this->unit);
            }    
            if(!empty($this->search)){  
                $db->grouped(function($q){
                    $q->like('labelName', "%$this->search%");
                });
            }                   
            $responseLabels = $db->getAll();      

            $return =  $this->session->prepareResponse($responseLabels);
            if($return){
                echo $return;
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            }
            else
            {
                $return['message'] = 'Labels can\'t found';   
                echo $this->session->prepareResponse($return);
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);         
            }       
        }
        else
        {
            return $check;
        }       
    }

    public function newLabel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("labels.add", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterLabel($input);     
            if(!$data){
                $return['message'] = 'Label can\'t added, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                try
                {
                    $add = $db->table('labels')->insert($data);
                    $data['labelID'] = $add;
                    $return['message'] = 'OK';
                    $return['data'] = $data;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(201);    
                }
                catch(PDOException $ex)
                {
                    $return['message'] = 'ERROR';
                    $return['data'] = $ex->errorInfo;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);   
                }
            }        
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    public function updateLabel(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->session->authUser = $this->session->jwtUser($request);        
        $check = $this->permissionCheck("labels.update", $response);       
        if(!is_object($check)){
            $db = $this->database; 
            $id = $args['label'];
            $id = (int)$id;       
            $labelDetail = $db->table('labels')
            ->where('groupID', $id)
            ->getAll();

            if(!$labelDetail){
                $return['message'] = 'Label can\'t found, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
            }

            $body = $request->getBody()->__toString();
            $input = json_decode($body, true);                            
            $data = $this->filterLabel($input);     
            if(!$data){
                $return['message'] = 'Label can\'t updated, please send correct information';
                $response->getBody()->write($this->session->prepareResponse($return));
                return $response->withHeader('Content-Type', 'application/json')
                ->withStatus(403);                    
            }
            else
            {
                try
                {
                    $data['labelID'] = $id;       
                    $db->table('labels')->where('labelID', $id)->update($data); 
                    
                    $table['table_label'] = $data['labelName'];
                    $db->table('device_table')->where('table_labelID', $id)->update($table);
                    $db->table('product_table')->where('table_labelID', $id)->update($table);

                    $return['message'] = 'OK';
                    $return['data'] = $data;                       
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);    
                }
                catch(PDOException $ex)
                {
                    $return['message'] = 'ERROR';
                    $return['data'] = $ex->errorInfo;
                    $response->getBody()->write($this->session->prepareResponse($return));
                    return $response->withHeader('Content-Type', 'application/json')
                    ->withStatus(403);   
                }
            }                            
        }
        if(is_object($check)){
            $return['message'] = 'You are not authorized to update this content. ';
            $response->getBody()->write($this->session->prepareResponse($return));
            return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
        }  
    }

    private function pagination($args){      
       $activePage = 1;
        if(isset($args['page'])){
            $activePage = (int)$args['page'];
        }          
        if($activePage <= 1) $activePage = 1;     
        $this->activePage = $activePage; 
        return $activePage;
    }
    
    private function previousPage($request){
        $active = $this->activePage;
        $previous = $active - 1;
        $uri = $request->getUri();        
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $path = $uri->getPath();
        $explode = explode('/', $path);
        $last = count($explode)-1;
        $explode[$last] = str_replace("$active", "$previous", $explode[$last]);
        $newPath = implode("/", $explode);
        $params = $request->getUri()->getQuery();
        if(strlen($params) > 0){
            $params = "?".$params;
        }
        else
        {
            $params = "";
        }
        return "$scheme://$host$newPath".$params;
    }
    private function nextPage($request){
        $active = $this->activePage;
        $previous = $active + 1;
        $uri = $request->getUri();        
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $path = $uri->getPath();
        $explode = explode('/', $path);
        $last = count($explode)-1;
        $explode[$last] = str_replace("$active", "$previous", $explode[$last]);
        $explode[$last] = str_replace("0", "$previous", $explode[$last]);
        $newPath = implode("/", $explode);
        
        $params = $request->getUri()->getQuery();
        if(strlen($params) > 0){
            $params = "?".$params;
        }
        else
        {
            $params = "";
        }
        return "$scheme://$host$newPath".$params;
    }

    private function filterProduct($input){
        $resp = array();
        if(isset($input['productName'])) 
            $resp['productName'] = strip_tags(trim($input['productName'])); 
        
        if(isset($input['productDescription'])) 
            $resp['productDescription'] = (int)$input['productDescription'];

        if(isset($input['productBrand'])) 
            $resp['productBrand'] = strip_tags(trim($input['productBrand'])); 

        if(isset($input['product_groupID'])) 
            $resp['product_groupID'] = (int)$input['product_groupID'];

        if(isset($input['productStatus'])) 
            $resp['productStatus'] = (int)$input['productStatus'];

        if(isset($input['productTags'])) 
            $resp['productTags'] = strip_tags(trim($input['productTags'])); 

        if(count($resp)){
            return $resp;
        }
        return false;       
    }

    private function filterProductTable($input){
        $resp = array();
        if(isset($input['tableName'])) 
            $resp['tableName'] = strip_tags(trim($input['tableName'])); 

        if(isset($input['table_label'])) 
            $resp['table_label'] = strip_tags(trim($input['table_label'])); 
        
        if(isset($input['table_labelID'])) 
            $resp['table_labelID'] = (int)($input['table_labelID']);

        if(isset($input['tableType'])) 
            $resp['tableType'] = (int)$input['tableType'];

        if(isset($input['tableProtocol'])) 
            $resp['tableProtocol'] = strip_tags(trim($input['tableProtocol'])); 

        if(isset($input['tableSubProtocol'])) 
            $resp['tableSubProtocol'] = strip_tags(trim($input['tableSubProtocol']));

        if(isset($input['tableAddress'])) 
            $resp['tableAddress'] = strip_tags(trim($input['tableAddress']));
        
        if(isset($input['tableSubAddress'])) 
            $resp['tableSubAddress'] = strip_tags(trim($input['tableSubAddress']));

        if(isset($input['tableDataType'])) 
            $resp['tableDataType'] = strip_tags(trim($input['tableDataType']));

        if(isset($input['table_isFunction'])) 
            $resp['table_isFunction'] =  (int)$input['table_isFunction'];

        if(isset($input['tableFactor'])) 
            $resp['tableFactor'] =  (int)$input['tableFactor'];

        if(isset($input['tableFactorSymbol'])) 
            $resp['tableFactorSymbol'] = strip_tags(trim($input['tableFactorSymbol']));

        if(isset($input['tableFunction'])) 
            $resp['tableFunction'] = strip_tags(trim($input['tableFunction']));

        if(isset($input['tableFunctionText'])) 
            $resp['tableFunctionText'] = strip_tags(trim($input['tableFunctionText']));

        if(isset($input['table_isIndex'])) 
            $resp['table_isIndex'] =  (int)$input['table_isIndex'];

        if(isset($input['tableUnit'])) 
            $resp['tableUnit'] = strip_tags(trim($input['tableUnit']));

        if(isset($input['tableMinValue'])) 
            $resp['tableMinValue'] = (int)$input['tableMinValue'];
        
        if(isset($input['tableMaxValue'])) 
            $resp['tableMaxValue'] = (int)$input['tableMaxValue'];

        if(isset($input['table_productID'])) 
            $resp['table_productID'] = (int)$input['table_productID'];

        if(isset($input['tablePeriod'])) 
            $resp['tablePeriod'] = (int)$input['tablePeriod'];

        if(isset($input['tableDeleteMonth'])) 
            $resp['tableDeleteMonth'] = (int)$input['tableDeleteMonth'];

        if(isset($input['tableNotes'])) 
            $resp['tableNotes'] = strip_tags(trim($input['tableNotes']));

        if(count($resp)){
            return $resp;
        }
        return false;       
    }

    private function filterProductGroup($input){
        $resp = array();
        if(isset($input['groupName'])) 
            $resp['groupName'] = strip_tags(trim($input['groupName'])); 
        
        if(isset($input['groupStatus'])) 
            $resp['groupStatus'] = (int)$input['groupStatus'];
       
        if(isset($input['group_ownerID'])) 
            $resp['group_ownerID'] = (int)$input['group_ownerID'];

        if(count($resp)){
            return $resp;
        }        
        return false;       
    }
    private function filterLabel($input){
        $resp = array();
        if(isset($input['labelName'])) 
        {
            $resp['labelName'] = strip_tags(trim($input['labelName'])); 
            $helper  = new \Iot\System\Helpers\Helper("test");

            $resp['labelCode'] = $helper->toSerp("testsd1 123");
        }      
        
        if(isset($input['labelUnit'])) 
            $resp['labelUnit'] = strip_tags(trim($input['labelUnit'])); 

        if(count($resp)){
            return $resp;
        }
        return false;       
    }

    private function checkParams($params, $args){
        $this->activePage = $this->pagination($args);

        if(isset($params['order_by'])){
            $this->order_by = strip_tags(trim($params['order_by']));
        }

        if(isset($params['sort_by'])){
            if($params['sort_by'] == 'asc'){
                $this->sort_by = 'asc';
            }
            else
            {
                $this->sort_by = 'desc';
            }            
        }

        if(isset($params['pagination'])){
            if($params['pagination'] == "false"){
                $this->pagination = false;
            }
            else
            {
                $this->pagination = true;                
            }            
        }

        if(isset($params['id'])){
            $this->productID = strip_tags(trim($params['id']));
        }
        if(isset($params['brand'])){
            $this->brand = strip_tags(trim($params['brand']));
        }
        if(isset($params['search'])){
            $this->search = strip_tags(trim($params['search']));
        }
        if(isset($params['group'])){
            $this->groupID = strip_tags(trim($params['group']));
        }
        if(isset($params['group_owner'])){
            $this->group_ownerID = strip_tags(trim($params['group_owner']));
        }
        if(isset($params['code'])){
            $this->code = strip_tags(trim($params['code']));
        }
        if(isset($params['unit'])){
            $this->unit = strip_tags(trim($params['unit']));
        }
        if(isset($params['groupSub'])){
            if($params['groupSub'] == "false"){
                $this->groupSub = false;
            }
            else
            {
                $this->groupSub = true;               
            }            
        }

        return true;
    }

    public function permissionCheck($needle = "all.permissions", $response){
        $permissions = json_decode($this->session->authUser->permission,true);  
        foreach($permissions as $permission){
           if($permission['Name'] == "all.permissions") {
               $this->superAdmin = true;
               return true;
           }
           if($permission['Name']  == $needle){
               return true;
           }
        }
        $return['response'] = "You are not authorized to view this content.";
        $response->getBody()->write($this->session->prepareResponse($return));
        return $response->withHeader('Content-Type', 'application/json')
        ->withStatus(401);        
    }
    
    public function userModems($auth){
        $groups = json_decode($this->authUser->groups, true); 
        $groupArray = array();
        foreach($groups as $group){  
            $id = (int)$group;      
            if(is_array($group)){
                foreach($group as $g){              
                    $id = (int)$g;
                    array_push($groupArray, $id);
                 } 
            }
            else
            {
                array_push($groupArray, $id);
            }
                     
        }
        $groupArray = array_unique($groupArray, SORT_NUMERIC);
       
        $modems = $this->database->select('modemID')
        ->table('modems')
        ->IN('modem_groupID', $groupArray)
        ->getAll();

        $responseModem = array();
        foreach($modems as $modem){
            array_push($responseModem, $modem->modemID);
        }

        return $responseModem;
    }

    protected function buildTree(&$elements, $parentId = 0){
        $branch = array();    
        foreach ($elements as $element) {
            if ($element->group_ownerID == $parentId) {
                $children = $this->buildTree($elements, $element->groupID);
                if ($children) {                   
                    $element->subGroup = $children;
                }
                array_push($branch, $element);
                //$branch[$element->groupID] = $element;
            }
        }
        return $branch;
    }
    
   


}