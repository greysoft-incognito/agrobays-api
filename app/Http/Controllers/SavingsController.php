<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class SavingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function plans()
    {
        $plans = Plan::all();

        return $this->buildResponse([
            'message' => $plans->isEmpty() ? 'There are no saving plans for now.' : '',
            'status' => $plans->isEmpty() ? 'info' : 'success',
            'response_code' => 200,
            'plans' => $plans,
        ]);
    }

    /**
     * Get a particular fruit bay plan by it's {id} or {slug}
     *
     * @param Request $request
     * @param string|integer $plan
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function getPlan(Request $request, $plan)
    {
        $plan = Plan::whereId($plan)->orWhere(['slug' => $plan])->first();

        return $this->buildResponse([
            'message' => !$plan ? 'The requested plan is no longer available' : '',
            'status' =>  !$plan ? 'error' : 'success',
            'response_code' => !$plan ? 404 : 200,
            'plan' => $plan,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function show(Plan $plan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function edit(Plan $plan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Plan $plan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Plan  $plan
     * @return \Illuminate\Http\Response
     */
    public function destroy(Plan $plan)
    {
        //
    }
}
