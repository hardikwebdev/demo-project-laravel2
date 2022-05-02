<div id="shareServiceModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<button type="button" class="close" onclick="cancelServiceShare()" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 px-md-5 new-seller-review">
				<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
				<lottie-player src="https://assets7.lottiefiles.com/packages/lf20_lw4olqnf.json" background="transparent"  speed="0.5"  style="width: 100%; height: 300px; margin-bottom: -50px; margin-top: -110px; align-self: center;" autoplay></lottie-player>
				<p class="font-weight-bold font-16 text-color-6 mt-3 mb-1 text-center">Spread the word!</p>
				<p class="text-center text-color-4">
					Loved the work by <strong>{{$Order->seller->username}}</strong>? Go ahead, share their {{ $Order->is_course == 1 ? 'course' : 'service'}} <br> with friends-they'll thank you for it. 
				</p>
				<ul class="list-inline mb-0 cus-social text-center">
					<li class="list-inline-item mr-3">
						<a target="_blank" class="share-icon" href="{{ $shareComponent['facebook'] }}" onclick="window.open('{{ $shareComponent['facebook'] }}', 'newwindow', 'width=500,height=400'); return false;">
							<i class="fab fa-facebook"></i>
						</a>
					</li>
					<li class="list-inline-item mr-3">
						<a target="_blank" class="share-icon" href="{{ $shareComponent['twitter']  }}" onclick="window.open('{{ $shareComponent['twitter'] }}', 'newwindow', 'width=500,height=400'); return false;">
							<i class="fab fa-twitter"></i>
						</a>
					</li>
					<li class="list-inline-item mr-3">
						<a target="_blank" class="share-icon" href="{{ $shareComponent['whatsapp'] }}" onclick="window.open('{{ $shareComponent['whatsapp'] }}', 'newwindow', 'width=500,height=400'); return false;">
							<i class="fab fa-whatsapp"></i>
						</a>
					</li>
				</ul>
				@if(((App\User::checkPremiumUser($Order->service->user->id) == true && ($Order->service->is_affiliate_link==1)) || (App\User::checkPremiumUser($Order->service->user->id) == false)) && $Order->is_course == 0 )
					<p class="text-center text-color-4 mt-2">Or copy Link</p>
					<div class="input-group mt-2">
						<input type="text" id="copyServicesLink" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{url(promoteservice.'/'.Auth::user()->affiliate_id.'/'.$Order->service->secret)}}" aria-describedby="basic-addon1">
						<div class="input-group-append">
							<a href="javascript:void(0)" class="input-group-text bg-transparent copy_btn border-left-0" onclick="copyServiceShareLink()" data-clipboard-text="{{url(promoteservice.'/'.Auth::user()->affiliate_id.'/'.$Order->service->secret)}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
						</div>
					</div>
				@endif
				<div class="modal-footer mt-0 border-0 justify-content-around">
					<button type="button" class="btn text-color-1 bg-transparent" onclick="cancelServiceShare()" data-dismiss="modal">Cancel</button>
				</div>
			</div>
		</div>
	</div>
</div>