> aharabara/habarnam
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
   - [ ] Refactor Application and app.php
   - [ ] Move some logic from ViewRender to Builders
      - [X] SurfaceBuilder
         - [x] PaddingBox
         - [X] MarginBox
      - [ ] ComponentBuilder
      - [ ] Move focus from index to selector
      - [ ] Encapsulate child components into container (do not expose them anymore)

#### v0.6.2
   - [ ] Add command mode to:
      - [ ] SelectBox
      - [ ] Autocomplete
      - [ ] etc.

#### V0.6.6
  - [ ] Fix position:relative and position:absolute
  - [ ] Fix password input displaying
  - [ ] Update htodo app.
 
#### v0.7.0
 - [ ] XML tree update for dynamical elements (append on event if no mapping was specified)
 - [ ] Iframe to load another view. (need to think)
 - [ ] Notifications
 - [ ] TemporaryComponentInterface
 - [ ] SelectBox
 - [ ] CheckBox

#### v0.8.0
   - [X] ScrollableTrait
   - [ ] Scrolling in:
       - [X] Ordered list
       - [ ] Textarea
       - [ ] SelectBox
       - [ ] CheckBox
       - [ ] Section


#### v0.9.0
  - [x] `Cursor extends Position` instead of `cursorPos`
  - [ ] Keyboard shortcuts
     - [X] ScrollableTrait
     - [X] Ordered list
  - [ ] Keyboard shortcuts

#### v1.0.0
   - [X] PSR-4 namespacing
   - [ ] Documentation
   - [ ] Final classes and accessible methods
   - [ ] 3 or more applications without bugs
   - [ ] composer create-project
   - [ ] 5 articles on different resources


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
