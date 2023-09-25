import { Component, OnInit, Input } from '@angular/core';
import { Md5 } from 'ts-md5/dist/md5';

@Component({
	selector: 'app-gravatar',
	templateUrl: './gravatar.component.html'
})
export class GravatarComponent implements OnInit {

	hash = '';

	@Input() email;
	@Input() size = 32;

	ngOnInit() {
		this.hash = Md5.hashStr(('' + this.email).trim().toLowerCase()) as string;
	}

}
