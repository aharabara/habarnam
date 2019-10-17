> aharabara/habarnam

```
(F)  - feature
(B)  - bug
(TD) - tehnical debt
(?)  - can't tell
```


## TUI Framework
#### V0.2.0
   - [x] Section
   - [x] List
   - [x] Input
   - [x] Divider
   - [x] Buttons
   - [x] TextArea
      - [x] Cursor displaing
      - [x] Editing in the middle

#### v0.2.1
   - [x] Enable extended ascii symbols

#### v0.3.0
   - [x] UI rendering via XML
   - [x] ComponentsContainerInterface
   
#### v0.3.1
   - [x] XML schema generation for UI, with attributes and events checks.

#### v0.3.2
   - [x] Correct surface partition for components      
   - [x] View switching
   

#### v0.4.0
   - [x] New line correct handling for textarea

#### v0.4.5
   - [x] CSS files for components instead of xml attributes

#### V0.4.7
 - [X] Container
 - [x] Eloquent and SQLite
 - [x] Queue process for delayed tasks
 - [x] Add migrations
 - [X] Loading state (overlay?) for component
    - [X] Done using position:relative

#### v0.5.0
   - [x] Script to create project tree for easy start
   - [ ] (TD) Refactor Application and app.php
   - [ ] (TD) Move some logic from ViewRender to Builders
      - [X] SurfaceBuilder
         - [x] PaddingBox
         - [X] MarginBox
      - [ ] (F)  ComponentBuilder
      - [ ] (TD) Encapsulate child components into container (do not expose them anymore)

#### v0.5.5
   - [ ] (TD) Replace ncurses with plain PHP code like escape codes.
   - [ ] (TD) Write tests ONLY for most important parts.
   - [ ] (TD) If anything breaks, then write a test, then fix it.

#### V0.6.6
  - [ ] (B) Fix position:relative and position:absolute
  - [ ] (B) Fix password input displaying

#### v1.0.0
   - [X] PSR-4 namespacing
   - [ ] (TD) View patch and then render instead of draw during calculation
   - [ ] (TD) Documentation
   - [ ] (TD) Final classes and accessible methods
   - [ ] (TD) Update htodo app.
   - [ ] (?)  5 articles on different resources

#### v1.0.2
  - [x] `Cursor extends Position` instead of `cursorPos`
  - [ ] (F) Keyboard shortcuts
  - [ ] (F) composer create-project
  - [ ] (F) 3 or more applications without bugs

#### v1.0.5
 - [ ] (F) Move focus from index to selector
 - [ ] (F) XML tree update for dynamical elements (append on event if no mapping was specified)

#### v1.1.0
 - [ ] (F) TemporaryComponentInterface
 - [ ] (F) Notifications
 - [ ] (F) SelectBox
 - [ ] (F) CheckBox

#### v1.2.0
 - [ ] (F) Iframe to load another view. (need to think)
 
#### v1.3.0
   - [X] ScrollableTrait
   - [ ] (TD) Scrolling in:
       - [X] Ordered list
       - [ ] Textarea
       - [ ] SelectBox
       - [ ] CheckBox
       - [ ] Section



#### ToDo
   - [ ] List
     - [ ] Reordering
   - [ ] Table
     - [ ] Reordering 
     - [ ] Columns and rows
     - [ ] Editable
   - [ ] Progress bar
   - [ ] Modal
   - [ ] Tabs
   - [ ] CheckBox
   - [ ] SelectBox
      - [ ] Multiple
   - [x] Buttons
   - [ ] TextArea
      - [ ] Read only
      - [ ] Syntax highlighting
   - [ ] Menu bar

 - Features
   - [ ] Autocomplete for input and textareas
   - [ ] Spellcheck for input and textareas
   - [ ] Copy&Paste
