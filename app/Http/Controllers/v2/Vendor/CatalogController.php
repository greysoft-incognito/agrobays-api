<?php

namespace App\Http\Controllers\v2\Vendor;

use App\EnumsAndConsts\HttpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\VendorCatalogItemCollection;
use App\Models\Food;
use App\Models\FruitBay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var \App\Models\Vendor $vendor */
        $vendor = $user->vendor;

        $query = $vendor->catalog()->getQuery()->when($request->search, function (Builder $query) use ($request) {
            $query->where(function (Builder $query) use ($request) {
                $query->whereHasMorph(
                    'catalogable',
                    [Food::class, FruitBay::class],
                    function (Builder $query) use ($request) {
                        $query->where('name', 'like', "%$request->search%");
                        $query->orWhereFulltext('description', $request->search);
                    }
                );
            });
        });

        $items = $query->paginate($request->get('limit', 30));

        return (new VendorCatalogItemCollection($items))->additional([
            'message' => HttpStatus::message(HttpStatus::OK),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'item_type' => ['required_without:items', 'string', 'min:1', 'in:Food,FruitBay'],
        ]);

        /** @var \Callable */
        $mdl = fn ($i) => str($i ?? 'Food')->prepend('\\App\\Models\\')->toString();

        $this->validate($request, [
            'qty' => ['required_without:items', 'nullable', 'numeric', 'min:1'],
            'item_id' => ['required_without:items', Rule::exists(app($mdl($request->item_type))->getTable(), 'id')],
            'items' => ['required_without:item_id', 'array', 'min:1'],
            'items.*.id' => ['required', 'numeric'],
            'items.*.qty' => ['required', 'numeric', 'min:1'],
            'items.*.type' => ['required', 'string', 'min:1', 'in:Food,FruitBay'],
        ], [], [
            'qty' => 'Quantity',
            'items.*.qty' => 'Quantity',
            'items.*.type' => 'Type',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        /** @var \App\Models\Vendor $vendor */
        $vendor = $user->vendor;

        $items = collect(
            $request->item_id
                ? [['id' => $request->item_id, 'type' => $mdl($request->item_type), 'qty' => $request->qty]]
                : $request->items
        )->map(fn ($item) => [
            'catalogable_id' => $item['id'],
            'catalogable_type' => $mdl($item['type']),
            'quantity' => $item['qty']
        ]);

        $vendor->catalog()->delete();
        $catalogItems = $vendor->catalog()->createMany($items);

        return (new VendorCatalogItemCollection($catalogItems))->additional([
            'message' => __('Your catalog has been updated and now contains :0 items.', [$items->count()]),
            'status' => 'success',
            'status_code' => HttpStatus::OK,
        ])->response()->setStatusCode(HttpStatus::OK);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
