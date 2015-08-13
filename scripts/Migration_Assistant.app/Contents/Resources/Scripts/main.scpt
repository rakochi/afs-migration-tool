FasdUAS 1.101.10   ��   ��    k             l      ��  ��    � � Created by Michael McGookey (mikemcgo) for the AFS Data Migration Tool
Written 6/10/15
Retrieves file path to executable in order to execute bash script
bash script contained in application     � 	 	|   C r e a t e d   b y   M i c h a e l   M c G o o k e y   ( m i k e m c g o )   f o r   t h e   A F S   D a t a   M i g r a t i o n   T o o l 
 W r i t t e n   6 / 1 0 / 1 5 
 R e t r i e v e s   f i l e   p a t h   t o   e x e c u t a b l e   i n   o r d e r   t o   e x e c u t e   b a s h   s c r i p t 
 b a s h   s c r i p t   c o n t a i n e d   i n   a p p l i c a t i o n   
  
 l     ��������  ��  ��        l     ��  ��    - 'Get File path to the executable package     �   N G e t   F i l e   p a t h   t o   t h e   e x e c u t a b l e   p a c k a g e      l     ����  O         r        c        n        m   	 ��
�� 
ctnr  l   	 ����  I   	�� ��
�� .earsffdralis        afdr   f    ��  ��  ��    m    ��
�� 
TEXT  o      ���� 0 x    m       �                                                                                  MACS  alis    t  Macintosh HD               ����H+  ��k
Finder.app                                                     �0 �u��        ����  	                CoreServices    ���*      �v,�    ��k��_��^  6Macintosh HD:System: Library: CoreServices: Finder.app   
 F i n d e r . a p p    M a c i n t o s h   H D  &System/Library/CoreServices/Finder.app  / ��  ��  ��         l     ��������  ��  ��      ! " ! l     �� # $��   # > 8Convert the file path to format that can be used by bash    $ � % % p C o n v e r t   t h e   f i l e   p a t h   t o   f o r m a t   t h a t   c a n   b e   u s e d   b y   b a s h "  & ' & l    (���� ( r     ) * ) n     + , + 1    ��
�� 
psxp , o    ���� 0 x   * o      ���� 0 	posixpath 	posixPath��  ��   '  - . - l     ��������  ��  ��   .  / 0 / l     �� 1 2��   1 J DAppend the location of the bash script to the path to the executable    2 � 3 3 � A p p e n d   t h e   l o c a t i o n   o f   t h e   b a s h   s c r i p t   t o   t h e   p a t h   t o   t h e   e x e c u t a b l e 0  4 5 4 l    6���� 6 r     7 8 7 b     9 : 9 o    ���� 0 	posixpath 	posixPath : m     ; ; � < < ~ M i g r a t i o n _ A s s i s t a n t . a p p / C o n t e n t s / R e s o u r c e s / S c r i p t s / b a s h _ t e s t . s h 8 o      ���� 0 location  ��  ��   5  = > = l     ��������  ��  ��   >  ? @ ? l     �� A B��   A = 7Open a new terminal window and run the script with bash    B � C C n O p e n   a   n e w   t e r m i n a l   w i n d o w   a n d   r u n   t h e   s c r i p t   w i t h   b a s h @  D�� D l   / E���� E O    / F G F k   ! . H H  I J I I  ! (�� K��
�� .coredoscnull��� ��� ctxt K b   ! $ L M L m   ! " N N � O O  / b i n / b a s h   - c   M o   " #���� 0 location  ��   J  P�� P I  ) .������
�� .miscactvnull��� ��� null��  ��  ��   G m     Q Q�                                                                                      @ alis    l  Macintosh HD               ����H+  ���Terminal.app                                                   �b�4�(        ����  	                	Utilities     ���*      �5&h    ������  2Macintosh HD:Applications: Utilities: Terminal.app    T e r m i n a l . a p p    M a c i n t o s h   H D  #Applications/Utilities/Terminal.app   / ��  ��  ��  ��       �� R S T U V��   R ��������
�� .aevtoappnull  �   � ****�� 0 x  �� 0 	posixpath 	posixPath�� 0 location   S �� W���� X Y��
�� .aevtoappnull  �   � **** W k     / Z Z   [ [  & \ \  4 ] ]  D����  ��  ��   X   Y  ������������ ;�� Q N����
�� .earsffdralis        afdr
�� 
ctnr
�� 
TEXT�� 0 x  
�� 
psxp�� 0 	posixpath 	posixPath�� 0 location  
�� .coredoscnull��� ��� ctxt
�� .miscactvnull��� ��� null�� 0� )j �,�&E�UO��,E�O��%E�O� ��%j O*j U T � ^ ^ T M a c i n t o s h   H D : U s e r s : m i k e m c g o o k e y : D o w n l o a d s : U � _ _ < / U s e r s / m i k e m c g o o k e y / D o w n l o a d s / V � ` ` � / U s e r s / m i k e m c g o o k e y / D o w n l o a d s / M i g r a t i o n _ A s s i s t a n t . a p p / C o n t e n t s / R e s o u r c e s / S c r i p t s / b a s h _ t e s t . s h ascr  ��ޭ