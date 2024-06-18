<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\articulos;
use App\Models\autores;
use App\Models\articulos_autores;
use App\Models\eventos;
use App\Models\areas;
use App\Models\revisores_articulos;

class ArticulosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Articulos=articulos_autores::OrderBy('articulo_id')->get();

        $Eventos=eventos::all();
        $Areas =areas::all();
        $Autores=autores::OrderBy('participante_id')->get();

        return view ('Articulos.index',compact('Articulos','Eventos','Areas','Autores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $datos=$request->all();
        //insertamos en articulo
        $articulo=articulos::create([
            'titulo'=>$datos['titulo'],
            'evento_id'=>$datos['evento_id'],
            'area_id'=>$datos['area_id'],
        ]);
        //obtenemos el id del articulo anteriormente ingresado
        $articuloId = $articulo->id;
        //insertamos datos en la tabla articulos_autores
        articulos_autores::create([
            'articulo_id'=>$articuloId,
            'autor_id'=>$datos['autor_id'],
        ]);

        return redirect ('/articulos');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $art = articulos_autores::with('autor')->find($id); // Carga ansiosa de la relación 'autor'

        $Eventos = Eventos::all();
        $Areas = Areas::all();
        $Autores = Autores::all();

        return view('Articulos.edit', compact('art', 'Eventos', 'Areas', 'Autores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $NuevosDatos = $request->all();
        //buscamos el articulo
        $articulo = articulos::find($id);

        //insertamos en articulo
        $articulo->update([
            'titulo'=>$NuevosDatos['titulo'],
            'area_id'=>$NuevosDatos['area_id'],
            'evento_id'=>$NuevosDatos['evento_id'],
        ]);
        
        //buscamos el autor
        $autor= articulos_autores::where('articulo_id',$articulo->id)->get();
        //insertamos en articulos_autores
        if ($autor->count() > 0) {
            $autor[0]->update([
                'autor_id' => $NuevosDatos['autor_id'],
            ]);
            return redirect('/articulos')->with('success', 'Artículo Modificado');
        }else{
            return redirect('/articulos')->with('error', 'No se encontro el Autor ');
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Buscar el artículo a eliminar
        $articulo = articulos::find($id);
        if (!$articulo){
                
            return redirect()->back()->with('error', 'No se encontro el articulo');
        }elseif (articulos_autores::where('articulo_id', $articulo->id)->count() > 0) {
              
              return redirect()->back()->with('error', 'No se puede eliminar el artículo porque tiene autores asociados');
        }elseif(revisores_articulos::where('articulo_id', $articulo->id)->count() > 0){
                return redirect()->back()->with('error', 'No se puede eliminar el artículo porque tiene revisores asociados');
        }
        // Eliminar el artículo
        $articulo->delete();
        // Redireccionar a la vista de artículos con un mensaje de éxito
        return redirect('/articulos')->with('success', 'Artículo eliminado correctamente');
    }
}
