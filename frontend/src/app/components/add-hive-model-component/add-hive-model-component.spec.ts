import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AddHiveModelComponent } from './add-hive-model-component';

describe('AddHiveModelComponent', () => {
  let component: AddHiveModelComponent;
  let fixture: ComponentFixture<AddHiveModelComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AddHiveModelComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AddHiveModelComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
