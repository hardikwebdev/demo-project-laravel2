<!--Dyanamic section for proposal edit, add, delete-->
         <form action="{{route('send.job_proposal')}}" method="post" id="job_proposal_1">
                                      <input type="hidden" name="_token" value="{{ Session::token() }}"
                                      >
                                      <input type="hidden" name="offer_id" value="{{$getData->secret}}">
                                      <input type="hidden" name="service_id" value="{{$getData->service->secret}}">
                                        <div class="side-layout m-brd-rad-15 mt-3 m-wh-brder-shadow">
                                            <p class="m-text-green mb-2"><strong>Update your proposal</strong></p>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="form-group m-primary-input">
                                                        <label class="mb-2 m-text-lgray">Delivery (Days)</label>
                                                        <input type="number" class="form-control " name="days" id="days" autocomplete="off" placeholder="Delivery (Days)" value="{{$getData->delivery_days}}">
                                                    </div>
                                                </div>
                                                <div class="col-6 pl-0">
                                                    <div class="form-group m-green-input">
                                                        <label class="mb-2 m-text-green">Price</label>
                                                         <input type="number" class="form-control " name="price" id="price_by" value="{{$getData->price}}" id="price" autocomplete="off" placeholder="Enter Price">
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="m-text-lgray mb-1">Define your proposal</p>
                                                <textarea class="form-control noresize m-bg-gray mb-1"  rows="5" cols="50" name="description" id="description">{{$getData->description}}</textarea>
                                            <button type="submit" class="btn m-btn-full m-btn-green m-brd-rad-15"><strong>Give Offer</strong></button>
                                        </div>
                                </form> 