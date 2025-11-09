import {
  Component,
  ElementRef,
  EventEmitter,
  HostListener,
  Output,
  ViewChild
} from '@angular/core';

@Component({
  selector: 'app-navbar',
  standalone: true,
  templateUrl: './navbar.html',
})
export class NavbarComponent {
  @Output() addHiveClicked = new EventEmitter<void>();
  @Output() themeToggled = new EventEmitter<void>();

  @Output() settingsClicked = new EventEmitter<void>();
  @Output() logoutClicked = new EventEmitter<void>();

  profileMenuOpen: boolean = false;

  @ViewChild('menuButton', { static: false }) menuButton?: ElementRef;
  @ViewChild('menuPanel', { static: false }) menuPanel?: ElementRef;

  toggleMenu() {
    this.profileMenuOpen = !this.profileMenuOpen;
  }

  closeMenu() {
    this.profileMenuOpen = false;
  }

  onSettings() {
    this.settingsClicked.emit();
    this.closeMenu();
  }

  onLogout() {
    this.logoutClicked.emit();
    this.closeMenu();
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent) {
    if (!this.profileMenuOpen) return;

    const target = event.target as Node;
    const clickedButton = this.menuButton?.nativeElement.contains(target);
    const clickedPanel = this.menuPanel?.nativeElement.contains(target);

    if (!clickedButton && !clickedPanel) {
      this.closeMenu();
    }
  }

  @HostListener('document:keydown.escape')
  onEscape() {
    this.closeMenu();
  }
}
