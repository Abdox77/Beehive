import { Component, EventEmitter, Output } from '@angular/core';

@Component({
  selector: 'app-navbar',
  standalone: true,
  templateUrl: './navbar.html',
})
export class NavbarComponent {
  @Output() addHiveClicked = new EventEmitter<void>();
  @Output() logoutClicked = new EventEmitter<void>();
}
